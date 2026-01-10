<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Acme Corporation',
                'registration_no' => 'REG-ACME-2020-001',
                'address' => '123 Business Street, Suite 100, New York, NY 10001, United States',
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Tech Solutions Inc.',
                'registration_no' => 'REG-TSI-2018-045',
                'address' => '456 Innovation Drive, San Francisco, CA 94102, United States',
            ],
        ];

        foreach ($companies as $companyData) {
            Company::create($companyData);
        }
    }
}
