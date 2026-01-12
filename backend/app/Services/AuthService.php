<?php

namespace App\Services;

use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

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
        // Remove role from data (not stored in users table)
        // Users don't get roles until they create/join a company
        unset($data['role']);

        $user = $this->userService->createUser($data);

        // Do NOT assign roles during registration - user has no company yet
        // Roles will be assigned when user creates a company (becomes owner/admin)
        // or when user is invited to join a company

        // Do NOT create employee record during registration
        // Employee records are created when user joins a company

        // Always log in the user after registration (for public self-registration)
        Auth::login($user);

        return [
            'user' => $user,
        ];
    }

    /**
     * Assign Spatie role to user based on role field
     *
     * During registration, roles are assigned with NULL company_id since
     * users haven't been associated with a company yet. Roles can be
     * re-assigned to specific companies later when users join companies.
     *
     * @param User $user The user instance
     * @param string $role The role from registration (admin, staff, employee)
     * @param int|null $companyId Optional company ID to assign role for specific company
     * @return void
     */
    protected function assignRoleToUser(User $user, string $role, ?int $companyId = null): void
    {
        // Map role field to Spatie roles
        $spatieRole = match ($role) {
            'admin' => 'admin',
            'staff' => 'staff',
            'employee' => 'user', // Employee maps to 'user' role in Spatie
            default => 'user',  // Default to 'user' role
        };

        // Set team context if company_id is provided
        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId($companyId);
            $user->assignRole($spatieRole);
        } finally {
            // Restore previous team context
            $registrar->setPermissionsTeamId($previousTeamId);
        }
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

        // Load employee and roles relationships
        $user->load(['employee', 'roles']);

        return $user;
    }
}
