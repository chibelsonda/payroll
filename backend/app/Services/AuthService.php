<?php

namespace App\Services;

use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    /**
     * Register a new user and create associated records
     *
     * @param array $data Registration data including first_name, last_name, email, password, role
     * @return array Array containing the created user
     * @throws \Exception If user creation fails or validation errors occur
     */
    public function register(array $data): array
    {
        // Extract role before creating user (role is not stored in users table)
        $role = $data['role'] ?? 'employee';
        unset($data['role']);

        $user = $this->userService->createUser($data);

        // Automatically assign Spatie role based on registration role
        $this->assignRoleToUser($user, $role);

        if ($user->isEmployee()) {
            $this->userService->createEmployeeForUser($user, []);
            $user->load('employee');
        }

        // Always log in the user after registration (for public self-registration)
        Auth::login($user);

        return [
            'user' => $user,
        ];
    }

    /**
     * Assign Spatie role to user based on role field
     *
     * @param User $user The user instance
     * @param string $role The role from registration (admin, staff, employee)
     * @return void
     */
    protected function assignRoleToUser(User $user, string $role): void
    {
        // Map role field to Spatie roles
        $spatieRole = match ($role) {
            'admin' => 'admin',
            'staff' => 'staff',
            'employee' => 'user', // Employee maps to 'user' role in Spatie
            default => 'user',  // Default to 'user' role
        };

        $user->assignRole($spatieRole);
    }

    /**
     * Authenticate a user with email and password
     *
     * @param array $credentials Login credentials (email and password)
     * @return array Array containing the authenticated user
     * @throws \Exception If credentials are invalid
     */
    public function login(array $credentials): array
    {
        if (!Auth::attempt($credentials, true)) {
            throw new InvalidCredentialsException();
        }

        /** @var User $user */
        $user = Auth::user();

        // Only load employee relationship if user is an employee
        if ($user->isEmployee()) {
            $user->load('employee');
        }

        return [
            'user' => $user,
        ];
    }

    /**
     * Logout the current user
     *
     * @param \Illuminate\Http\Request $request The current HTTP request
     * @return void
     */
    public function logout(\Illuminate\Http\Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * Get the currently authenticated user with relationships loaded
     *
     * @param \Illuminate\Http\Request $request The current HTTP request
     * @return User The authenticated user with appropriate relationships loaded
     */
    public function getCurrentUser(\Illuminate\Http\Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        // Only load employee relationship if user is an employee
        if ($user->isEmployee()) {
            $user->load('employee');
        }

        return $user;
    }
}
