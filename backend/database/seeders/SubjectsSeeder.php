<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubjectsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Subject::create([
            'name' => 'Mathematics',
            'description' => 'Basic mathematics course',
            'credits' => 3,
        ]);

        \App\Models\Subject::create([
            'name' => 'English',
            'description' => 'English language course',
            'credits' => 2,
        ]);

        \App\Models\Subject::create([
            'name' => 'Science',
            'description' => 'General science course',
            'credits' => 4,
        ]);
    }
}
