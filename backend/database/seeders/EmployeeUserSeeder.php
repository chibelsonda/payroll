<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeeUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::factory()->create([
            'first_name' => 'Employee',
            'last_name' => 'User',
            'email' => 'employee@example.com',
        ]);

        // Assign user role using Spatie Permission (employees use 'user' role)
        $user->assignRole('user');

        \App\Models\Employee::create([
            'user_id' => $user->id,
            'employee_id' => 'EMP0001',
        ]);
    }
}
