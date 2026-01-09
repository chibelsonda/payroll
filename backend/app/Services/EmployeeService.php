<?php

namespace App\Services;

use App\Models\Employee;

class EmployeeService
{
    /**
     * Get all employees with pagination and related data
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated employees with user relationships
     */
    public function getAllEmployees()
    {
        return Employee::with('user')->paginate(config('application.pagination.per_page'));
    }

    /**
     * Find an employee by their UUID
     *
     * @param string $uuid The UUID of the employee
     * @return Employee|null The employee instance or null if not found
     */
    public function findByUuid(string $uuid): ?Employee
    {
        return Employee::where('uuid', $uuid)->first();
    }

    /**
     * Create a new employee record
     *
     * @param array $data Employee data including user_id and employee_id
     * @return Employee The created employee instance
     * @throws \Exception If employee creation fails
     */
    public function createEmployee(array $data): Employee
    {
        return Employee::create([
            'user_id' => $data['user_id'],
            'employee_id' => $data['employee_id'],
        ]);
    }

    /**
     * Update an existing employee record
     *
     * @param Employee $employee The employee to update
     * @param array $data The data to update
     * @return Employee The updated employee instance with fresh relationships
     */
    public function updateEmployee(Employee $employee, array $data): Employee
    {
        $employee->update($data);
        return $employee->fresh(['user']);
    }

    /**
     * Delete an employee record from the database
     *
     * @param Employee $employee The employee to delete
     * @return bool True if deletion was successful
     */
    public function deleteEmployee(Employee $employee): bool
    {
        return $employee->delete();
    }

    /**
     * Get an employee with all related details loaded
     *
     * @param Employee $employee The employee instance
     * @return Employee The employee with user relationship loaded
     */
    public function getEmployeeWithDetails(Employee $employee): Employee
    {
        return $employee->load(['user']);
    }
}
