<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Create a new user with the provided data
     *
     * @param array $data User data including first_name, last_name, email, password
     * @return User The created user instance
     * @throws \Exception If user creation fails
     */
    public function createUser(array $data): User
    {
        return User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    /**
     * Find a user by their UUID
     *
     * @param string $uuid The UUID of the user
     * @return User|null The user instance or null if not found
     */
    public function findByUuid(string $uuid): ?User
    {
        return User::where('uuid', $uuid)->first();
    }

    /**
     * Get all users with pagination
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated users with student relationship
     */
    public function getAllUsers()
    {
        return User::with('student')->paginate(config('application.pagination.per_page'));
    }

    /**
     * Update an existing user with new data
     *
     * @param User $user The user to update
     * @param array $data The data to update
     * @return User The updated user instance
     */
    public function updateUser(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    /**
     * Delete a user from the database
     *
     * @param User $user The user to delete
     * @return bool True if deletion was successful
     */
    public function deleteUser(User $user): bool
    {
        return $user->delete();
    }

    /**
     * Create a student record for an existing user
     *
     * @param User $user The user to create a student record for
     * @param array $data Additional student data (optional student_id)
     * @return \App\Models\Student The created student instance
     */
    public function createStudentForUser(User $user, array $data): \App\Models\Student
    {
        return $user->student()->create([
            'student_id' => $data['student_id'] ?? config('application.student.id_prefix') . str_pad($user->id, 4, '0', STR_PAD_LEFT),
        ]);
    }
}