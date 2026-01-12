<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDeduction;
use Illuminate\Database\Eloquent\Collection;

class EmployeeDeductionService
{
    /**
     * Get all employee deductions for a specific employee
     */
    public function getEmployeeDeductions(string $employeeUuid): Collection
    {
        $employee = Employee::where('uuid', $employeeUuid)->firstOrFail();
        
        return EmployeeDeduction::where('employee_id', $employee->id)
            ->with(['deduction', 'employee.user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Assign a deduction to an employee
     */
    public function assignDeduction(array $data): EmployeeDeduction
    {
        // Check if deduction already exists for this employee
        $existing = EmployeeDeduction::where('employee_id', $data['employee_id'])
            ->where('deduction_id', $data['deduction_id'])
            ->first();

        if ($existing) {
            // Update existing record
            $existing->update(['amount' => $data['amount']]);
            return $existing->fresh(['deduction', 'employee.user']);
        }

        return EmployeeDeduction::create($data)->load(['deduction', 'employee.user']);
    }

    /**
     * Remove a deduction from an employee
     */
    public function removeDeduction(string $employeeUuid, string $deductionUuid): bool
    {
        $employee = Employee::where('uuid', $employeeUuid)->firstOrFail();
        $deduction = \App\Models\Deduction::where('uuid', $deductionUuid)->firstOrFail();

        $employeeDeduction = EmployeeDeduction::where('employee_id', $employee->id)
            ->where('deduction_id', $deduction->id)
            ->firstOrFail();

        return $employeeDeduction->delete();
    }

    /**
     * Update an employee deduction amount
     */
    public function updateEmployeeDeduction(EmployeeDeduction $employeeDeduction, array $data): EmployeeDeduction
    {
        $employeeDeduction->update($data);
        return $employeeDeduction->fresh(['deduction', 'employee.user']);
    }
}
