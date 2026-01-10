<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();

        if ($companies->isEmpty()) {
            $this->command->warn('No companies found. Please run CompanySeeder first.');
            return;
        }

        $departments = [
            'Human Resources',
            'Information Technology',
            'Finance',
            'Operations',
            'Sales',
            'Marketing',
        ];

        foreach ($companies as $company) {
            foreach ($departments as $departmentName) {
                Department::create([
                    'uuid' => (string) Str::uuid(),
                    'company_id' => $company->id,
                    'name' => $departmentName,
                ]);
            }
        }
    }
}
