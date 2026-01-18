<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Salary;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SalaryService
{
    /**
     * Get all salaries with pagination
     */
    public function getAllSalaries(): LengthAwarePaginator
    {
        return Salary::with(['employee.user'])
            ->orderBy('effective_from', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(config('application.pagination.per_page'));
    }

    /**
     * Get salary history for a specific employee
     */
    public function getEmployeeSalaries(string $employeeUuid): Collection
    {
        $employee = Employee::where('uuid', $employeeUuid)->firstOrFail();
        
        return Salary::where('employee_id', $employee->id)
            ->orderBy('effective_from', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find salary by UUID
     */
    public function findSalaryByUuid(string $uuid): ?Salary
    {
        return Salary::where('uuid', $uuid)->with(['employee.user'])->first();
    }

    /**
     * Create a new salary record
     * Note: Always creates a new record, never updates existing
     */
    public function createSalary(array $data): Salary
    {
        return Salary::create($data);
    }

    /**
     * Update an existing salary
     * Note: In practice, salary changes should create new records
     */
    public function updateSalary(Salary $salary, array $data): Salary
    {
        $salary->update($data);
        return $salary->fresh(['employee.user']);
    }

    /**
     * Delete a salary record
     */
    public function deleteSalary(Salary $salary): bool
    {
        return $salary->delete();
    }
}
