<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user without company_id (user will create/join company during onboarding)
        $admin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'uuid' => (string) Str::uuid(),
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
            ]
        );

        // Note: Roles are assigned when user creates/joins a company
        // For seeding purposes, we don't assign roles here since user has no company_id yet
    }
}
