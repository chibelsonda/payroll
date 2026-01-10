<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = Department::all();

        if ($departments->isEmpty()) {
            $this->command->warn('No departments found. Please run DepartmentSeeder first.');
            return;
        }

        // Define positions by department type
        $positionsByDepartment = [
            'Human Resources' => ['HR Manager', 'HR Officer', 'Recruitment Specialist', 'Payroll Administrator'],
            'Information Technology' => ['IT Manager', 'Senior Developer', 'Junior Developer', 'Systems Administrator', 'QA Engineer'],
            'Finance' => ['Finance Manager', 'Senior Accountant', 'Accountant', 'Financial Analyst'],
            'Operations' => ['Operations Manager', 'Operations Coordinator', 'Logistics Specialist'],
            'Sales' => ['Sales Manager', 'Sales Representative', 'Account Executive', 'Sales Support'],
            'Marketing' => ['Marketing Manager', 'Marketing Specialist', 'Content Creator', 'Digital Marketing Analyst'],
        ];

        foreach ($departments as $department) {
            $departmentName = $department->name;
            $positions = $positionsByDepartment[$departmentName] ?? ['Manager', 'Officer', 'Specialist'];

            foreach ($positions as $positionTitle) {
                Position::create([
                    'uuid' => (string) Str::uuid(),
                    'department_id' => $department->id,
                    'title' => $positionTitle,
                ]);
            }
        }
    }
}
