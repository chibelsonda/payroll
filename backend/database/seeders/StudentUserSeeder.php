<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StudentUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'employee@example.com'],
            [
                'uuid' => (string) Str::uuid(),
                'first_name' => 'Employee',
                'last_name' => 'User',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
            ]
        );

        // Note: Roles are assigned when user creates/joins a company
        // For seeding purposes, we don't assign roles here since user has no company_id yet

        Employee::firstOrCreate(
            ['user_id' => $user->id],
            [
                'uuid' => (string) Str::uuid(),
                'employee_no' => 'EMP0001',
                'status' => 'active',
            ]
        );
    }
}
