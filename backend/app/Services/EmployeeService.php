<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

    /**
     * Import employees from a CSV file.
     * Expected headers (case-insensitive, exact set): first_name,last_name,email,password
     * Optional header: employee_no (auto-generated if missing).
     *
     * @return array{created:int,failed:int,errors:array<int,array{row:int,message:string}>}
     */
    public function importFromCsv(UploadedFile $file, ?int $companyId = null): array
    {
        $created = 0;
        $errors = [];
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new \RuntimeException('Unable to read the uploaded CSV file.');
        }

        $headers = null;
        $rowNumber = 0;
        $requiredHeaders = ['first_name', 'last_name', 'email', 'password'];
        $optionalHeaders = ['employee_no'];
        $allowedHeaders = array_merge($requiredHeaders, $optionalHeaders);

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rowNumber++;

            // Capture headers
            if ($headers === null) {
                $headers = array_map(fn ($h) => strtolower(trim($h)), $row);

                // Validate header set matches expectation
                $headerSet = array_unique($headers);
                sort($headerSet);
                $expectedSet = $allowedHeaders;
                sort($expectedSet);

                // Required must be present, and no unexpected headers
                $missing = array_diff($requiredHeaders, $headerSet);
                $unexpected = array_diff($headerSet, $allowedHeaders);

                if (!empty($missing) || !empty($unexpected)) {
                    fclose($handle);
                    $missingMsg = empty($missing) ? '' : ('Missing: ' . implode(', ', $missing) . '. ');
                    $unexpectedMsg = empty($unexpected) ? '' : ('Unexpected: ' . implode(', ', $unexpected) . '.');
                    throw new \InvalidArgumentException(
                        'Invalid CSV headers. Expected: first_name,last_name,email,password'
                        . ' (optional: employee_no). '
                        . $missingMsg . $unexpectedMsg
                    );
                }
                continue;
            }

            // Skip empty lines
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $rowData = [];
            foreach ($headers as $index => $column) {
                $rowData[$column] = isset($row[$index]) ? trim((string) $row[$index]) : null;
            }

            try {
                $payload = [
                    'first_name' => $rowData['first_name'] ?? null,
                    'last_name' => $rowData['last_name'] ?? null,
                    'email' => $rowData['email'] ?? null,
                    'password' => $rowData['password'] ?? null,
                    'employee_no' => $rowData['employee_no'] ?? null,
                    'company_id' => $companyId,
                ];

                // Normalize blanks to null
                foreach ($payload as $key => $value) {
                    if (is_string($value) && trim($value) === '') {
                        $payload[$key] = null;
                    }
                }

                // Basic required checks before delegating to createEmployee()
                $required = ['first_name', 'last_name', 'email', 'password'];
                foreach ($required as $field) {
                    if (empty($payload[$field])) {
                        throw new \InvalidArgumentException("Missing required field: {$field}");
                    }
                }

                // Auto-generate employee_no if not provided to satisfy uniqueness
                if (empty($payload['employee_no'])) {
                    $payload['employee_no'] = 'EMP-' . strtoupper(Str::random(8));
                }

                $this->createEmployee($payload);
                $created++;
            } catch (\Throwable $e) {
                Log::warning('Employee import row failed', [
                    'row' => $rowNumber,
                    'error' => $e->getMessage(),
                ]);

                $friendly = 'Row failed to import. Please check the data.';
                if ($e instanceof QueryException) {
                    $msg = $e->getMessage();
                    if (str_contains($msg, 'users_email_unique')) {
                        $friendly = 'Email already exists for another user.';
                    } elseif (str_contains($msg, 'employees_employee_no_unique')) {
                        $friendly = 'Employee number already exists.';
                    } else {
                        $friendly = 'Database constraint error on this row.';
                    }
                } elseif ($e instanceof \InvalidArgumentException) {
                    $friendly = $e->getMessage();
                }

                $errors[] = [
                    'row' => $rowNumber,
                    'message' => $friendly,
                ];
            }
        }

        fclose($handle);

        return [
            'created' => $created,
            'failed' => count($errors),
            'errors' => $errors,
        ];
    }
}
