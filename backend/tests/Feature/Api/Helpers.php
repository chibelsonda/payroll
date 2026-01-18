<?php

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

if (!function_exists('createAuthenticatedAdmin')) {
    function createAuthenticatedAdmin(): User
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('admin');
        return $user;
    }
}

if (!function_exists('createAuthenticatedEmployee')) {
    function createAuthenticatedEmployee(): User
    {
        $user = User::factory()->create([
            'email' => 'employee@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('user');

        Employee::create([
            'user_id' => $user->id,
            'employee_no' => 'EMP001',
            'status' => 'active',
        ]);

        return $user;
    }
}

if (!function_exists('createAuthenticatedEmployeeWithEmployee')) {
    function createAuthenticatedEmployeeWithEmployee(): array
    {
        $user = User::factory()->create([
            'email' => 'employee@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('user');

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_no' => 'EMP001',
            'status' => 'active',
        ]);

        return ['user' => $user, 'employee' => $employee];
    }
}

if (!function_exists('loginUser')) {
    function loginUser(User $user): array
    {
        $response = test()->postJson('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);
        return $response->json();
    }
}
