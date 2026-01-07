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
     * @return array Array containing the created user and access token
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
        }

        $token = $user->createToken(config('application.auth.token_name'))->plainTextToken;

        return [
            'user' => $user->load('student'),
            'token' => $token,
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
     * @return array Array containing the authenticated user and access token
     * @throws \Exception If credentials are invalid
     */
    public function login(array $credentials): array
    {
        if (!Auth::attempt($credentials)) {
            throw new InvalidCredentialsException();
        }

        $user = Auth::user();
        $token = $user->createToken(config('application.auth.token_name'))->plainTextToken;

        return [
            'user' => $user->load('student'),
            'token' => $token,
        ];
    }

    /**
     * Logout the current user by revoking their access token
     *
     * @param \Illuminate\Http\Request $request The current HTTP request
     * @return void
     */
    public function logout(\Illuminate\Http\Request $request): void
    {
        $request->user()->currentAccessToken()->delete();
    }

    /**
     * Get the currently authenticated user with student relationship
     *
     * @param \Illuminate\Http\Request $request The current HTTP request
     * @return User The authenticated user with student relationship loaded
     */
    public function getCurrentUser(\Illuminate\Http\Request $request): User
    {
        return $request->user()->load('student');
    }
}
