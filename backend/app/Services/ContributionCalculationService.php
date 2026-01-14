<?php

namespace App\Services;

/**
 * Contribution Calculation Service
 *
 * Implements Philippine government contributions calculation:
 * - SSS (Social Security System)
 * - PhilHealth (Philippine Health Insurance Corporation)
 * - Pag-IBIG (Home Development Mutual Fund)
 */
class ContributionCalculationService
{
    /**
     * Calculate SSS contribution
     *
     * Based on SSS contribution table (updated 2023)
     *
     * @param float $monthlySalary Monthly salary
     * @return array ['employee' => float, 'employer' => float, 'total' => float]
     */
    public function calculateSSS(float $monthlySalary): array
    {
        // SSS contribution is based on salary brackets
        // For simplicity, using fixed percentage (can be enhanced with bracket table)

        // SSS salary credit ranges from 1,000 to 30,000
        $salaryCredit = max(1000, min(30000, $monthlySalary));

        // Round to nearest 500 for salary credit
        $salaryCredit = round($salaryCredit / 500) * 500;
        $salaryCredit = max(1000, min(30000, $salaryCredit));

        // Employee share: 11% of salary credit
        // Employer share: 11.5% of salary credit (employer pays slightly more)
        // Total: 22.5% of salary credit

        // Simplified: Using fixed rates
        $employeeShare = $salaryCredit * 0.11;
        $employerShare = $salaryCredit * 0.115;
        $total = $employeeShare + $employerShare;

        return [
            'employee' => round($employeeShare, 2),
            'employer' => round($employerShare, 2),
            'total' => round($total, 2),
            'salary_credit' => $salaryCredit,
        ];
    }

    /**
     * Calculate PhilHealth contribution
     *
     * Based on PhilHealth contribution table (2023)
     *
     * @param float $monthlySalary Monthly salary
     * @return array ['employee' => float, 'employer' => float, 'total' => float]
     */
    public function calculatePhilHealth(float $monthlySalary): array
    {
        // PhilHealth contribution rates (2023)
        // Premium rate: 4.5% of monthly salary (shared 50-50 between employee and employer)
        // Minimum salary: 10,000
        // Maximum salary: 100,000 (premium ceiling)

        $premiumBase = max(10000, min(100000, $monthlySalary));

        // 4.5% of premium base, shared 50-50
        $totalPremium = $premiumBase * 0.045;
        $employeeShare = $totalPremium / 2;
        $employerShare = $totalPremium / 2;

        return [
            'employee' => round($employeeShare, 2),
            'employer' => round($employerShare, 2),
            'total' => round($totalPremium, 2),
            'premium_base' => $premiumBase,
        ];
    }

    /**
     * Calculate Pag-IBIG contribution
     *
     * Based on Pag-IBIG contribution table (2023)
     *
     * @param float $monthlySalary Monthly salary
     * @return array ['employee' => float, 'employer' => float, 'total' => float]
     */
    public function calculatePagIbig(float $monthlySalary): array
    {
        // Pag-IBIG contribution rates (2023)
        // For employees earning 1,500 and below: 1% employee, 2% employer
        // For employees earning above 1,500: 2% employee, 2% employer
        // Maximum contribution base: 5,000

        $contributionBase = min(5000, $monthlySalary);

        if ($monthlySalary <= 1500) {
            $employeeRate = 0.01; // 1%
            $employerRate = 0.02; // 2%
        } else {
            $employeeRate = 0.02; // 2%
            $employerRate = 0.02; // 2%
        }

        $employeeShare = $contributionBase * $employeeRate;
        $employerShare = $contributionBase * $employerRate;
        $total = $employeeShare + $employerShare;

        return [
            'employee' => round($employeeShare, 2),
            'employer' => round($employerShare, 2),
            'total' => round($total, 2),
            'contribution_base' => $contributionBase,
        ];
    }

    /**
     * Calculate all government contributions
     *
     * @param float $monthlySalary Monthly salary
     * @return array [
     *     'sss' => ['employee' => float, 'employer' => float, 'total' => float],
     *     'philhealth' => ['employee' => float, 'employer' => float, 'total' => float],
     *     'pagibig' => ['employee' => float, 'employer' => float, 'total' => float],
     *     'total_employee' => float,
     *     'total_employer' => float,
     * ]
     */
    public function calculateAllContributions(float $monthlySalary): array
    {
        $sss = $this->calculateSSS($monthlySalary);
        $philhealth = $this->calculatePhilHealth($monthlySalary);
        $pagibig = $this->calculatePagIbig($monthlySalary);

        $totalEmployee = $sss['employee'] + $philhealth['employee'] + $pagibig['employee'];
        $totalEmployer = $sss['employer'] + $philhealth['employer'] + $pagibig['employer'];

        return [
            'sss' => $sss,
            'philhealth' => $philhealth,
            'pagibig' => $pagibig,
            'total_employee' => round($totalEmployee, 2),
            'total_employer' => round($totalEmployer, 2),
        ];
    }
}
