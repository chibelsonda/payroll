<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudentUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::factory()->create([
            'first_name' => 'Student',
            'last_name' => 'User',
            'email' => 'student@example.com',
        ]);

        // Assign user role using Spatie Permission (students use 'user' role)
        $user->assignRole('user');

        \App\Models\Student::create([
            'user_id' => $user->id,
            'student_id' => 'STU0001',
        ]);
    }
}
