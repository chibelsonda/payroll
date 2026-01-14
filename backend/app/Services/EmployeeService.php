<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    /**
     * Get all employees with pagination and related data
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated employees with user relationships
     */
    public function getAllEmployees()
    {
        return Employee::with(['user', 'company', 'department', 'position'])->paginate(config('application.pagination.per_page'));
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
     * Create a new employee record with associated user
     *
     * @param array $data Employee and user data including first_name, last_name, email, password, employee_no
     * @return Employee The created employee instance with user relationship loaded
     * @throws \Exception If employee creation fails
     */
    public function createEmployee(array $data): Employee
    {
        // If user_id is provided, create employee for existing user (backward compatibility)
        if (isset($data['user_id'])) {
            $employeeData = [
                'user_id' => $data['user_id'],
                'employee_no' => $data['employee_no'] ?? null,
            ];

            // Add optional fields if provided (use array_key_exists to allow null values)
            $optionalFields = ['company_id', 'department_id', 'position_id', 'employment_type', 'hire_date', 'termination_date', 'status'];
            foreach ($optionalFields as $field) {
                if (array_key_exists($field, $data)) {
                    $employeeData[$field] = $data[$field];
                }
            }

            return Employee::create($employeeData);
        }

        // Otherwise, create both user and employee in a transaction
        return DB::transaction(function () use ($data) {
            // Create user
            $userService = app(UserService::class);
            $user = $userService->createUser([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            // Assign 'user' role (employees use 'user' role in Spatie)
            $user->assignRole(Role::Employee->value);

            // Prepare employee data
            $employeeData = [
                'employee_no' => $data['employee_no'] ?? null,
            ];

            // Add optional fields if provided (use array_key_exists to allow null values)
            $optionalFields = ['company_id', 'department_id', 'position_id', 'employment_type', 'hire_date', 'termination_date', 'status'];
            foreach ($optionalFields as $field) {
                if (array_key_exists($field, $data)) {
                    $employeeData[$field] = $data[$field];
                }
            }

            // Create employee record
            $employee = $user->employee()->create($employeeData);

            // Load relationships and return
            return $employee->load(['user', 'company', 'department', 'position']);
        });
    }

    /**
     * Update an existing employee record and associated user
     *
     * @param Employee $employee The employee to update
     * @param array $data The data to update (may include user fields: first_name, last_name, email)
     * @return Employee The updated employee instance with fresh relationships
     */
    public function updateEmployee(Employee $employee, array $data): Employee
    {
        // Ensure user relationship is loaded
        if (!$employee->relationLoaded('user')) {
            $employee->load('user');
        }

        // Separate user fields from employee fields
        $userFields = [];
        $employeeFields = [];

        $userFieldKeys = ['first_name', 'last_name', 'email'];

        // Define employee field keys that should always be included (even if null)
        $employeeFieldKeys = ['employee_no', 'company_id', 'department_id', 'position_id', 'employment_type', 'hire_date', 'termination_date', 'status'];

        foreach ($data as $key => $value) {
            if (in_array($key, $userFieldKeys)) {
                $userFields[$key] = $value;
            } elseif (in_array($key, $employeeFieldKeys)) {
                // Always include employee fields, even if null (to allow clearing values)
                $employeeFields[$key] = $value;
            }
        }

        // Update user if there are user fields
        if (!empty($userFields)) {
            $user = $employee->user;
            if ($user) {
                $user->update($userFields);
            } else {
                throw new \Exception('Employee does not have an associated user');
            }
        }

        // Update employee if there are employee fields
        if (!empty($employeeFields)) {
            $employee->update($employeeFields);
        }

        return $employee->fresh(['user', 'company', 'department', 'position']);
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
        return $employee->load(['user', 'company', 'department', 'position']);
    }
}
