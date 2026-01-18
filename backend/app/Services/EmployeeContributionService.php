<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeContribution;
use Illuminate\Database\Eloquent\Collection;

class EmployeeContributionService
{
    /**
     * Get all employee contributions for a specific employee
     */
    public function getEmployeeContributions(string $employeeUuid): Collection
    {
        $employee = Employee::where('uuid', $employeeUuid)->firstOrFail();
        
        return EmployeeContribution::where('employee_id', $employee->id)
            ->with(['contribution', 'employee.user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Assign a contribution to an employee
     */
    public function assignContribution(array $data): EmployeeContribution
    {
        // Check if contribution already exists for this employee
        $existing = EmployeeContribution::where('employee_id', $data['employee_id'])
            ->where('contribution_id', $data['contribution_id'])
            ->first();

        if ($existing) {
            // Already assigned, return existing
            return $existing->fresh(['contribution', 'employee.user']);
        }

        return EmployeeContribution::create($data)->load(['contribution', 'employee.user']);
    }

    /**
     * Remove a contribution from an employee
     */
    public function removeContribution(string $employeeUuid, string $contributionUuid): bool
    {
        $employee = Employee::where('uuid', $employeeUuid)->firstOrFail();
        $contribution = \App\Models\Contribution::where('uuid', $contributionUuid)->firstOrFail();

        $employeeContribution = EmployeeContribution::where('employee_id', $employee->id)
            ->where('contribution_id', $contribution->id)
            ->firstOrFail();

        return $employeeContribution->delete();
    }
}
