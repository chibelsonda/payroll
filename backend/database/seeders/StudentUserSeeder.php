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
        $user = \App\Models\User::factory()->create([
            'uuid' => (string) Str::uuid(),
            'first_name' => 'Employee',
            'last_name' => 'User',
            'email' => 'employee@example.com',
        ]);

        // Assign user role using Spatie Permission (employees use 'user' role)
        $user->assignRole('user');

        Employee::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'employee_no' => 'EMP0001',
            'status' => 'active',
        ]);
    }
}
