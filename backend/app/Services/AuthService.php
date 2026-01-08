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
        $role = $data['role'] ?? 'student';
        unset($data['role']);

        $user = $this->userService->createUser($data);

        // Automatically assign Spatie role based on registration role
        $this->assignRoleToUser($user, $role);

        if ($user->isStudent()) {
            $this->userService->createStudentForUser($user, []);
            $user->load('student');
        }

        Auth::login($user);

        return [
            'user' => $user,
        ];
    }

    /**
     * Assign Spatie role to user based on role field
     *
     * @param User $user The user instance
     * @param string $role The role from registration (admin, staff, student)
     * @return void
     */
    protected function assignRoleToUser(User $user, string $role): void
    {
        // Map role field to Spatie roles
        $spatieRole = match ($role) {
            'admin' => 'admin',
            'staff' => 'staff',
            'student' => 'user', // Student maps to 'user' role in Spatie
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

        // Only load student relationship if user is a student
        if ($user->isStudent()) {
            $user->load('student');
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

        // Only load student relationship if user is a student
        if ($user->isStudent()) {
            $user->load('student');
        }

        return $user;
    }
}
