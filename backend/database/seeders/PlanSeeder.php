<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Starter',
                'price' => 999.00,
                'billing_cycle' => 'monthly',
                'max_employees' => 10,
                'is_active' => true,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Pro',
                'price' => 1999.00,
                'billing_cycle' => 'monthly',
                'max_employees' => 50,
                'is_active' => true,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Business',
                'price' => 3999.00,
                'billing_cycle' => 'monthly',
                'max_employees' => 200,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::firstOrCreate(
                ['name' => $planData['name']],
                $planData
            );
        }
    }
}
