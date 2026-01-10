<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Department;

class DepartmentService
{
    /**
     * Get all departments, optionally filtered by company UUID
     *
     * @param string|null $companyUuid Optional UUID of the company to filter by
     * @return \Illuminate\Database\Eloquent\Collection Collection of departments
     */
    public function getAllDepartments(?string $companyUuid = null)
    {
        $query = Department::query()->with('company');

        if ($companyUuid) {
            $company = Company::where('uuid', $companyUuid)->first();
            if ($company) {
                $query->where('company_id', $company->id);
            } else {
                // Return empty collection if company not found
                return collect([]);
            }
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Find a department by its UUID
     *
     * @param string $uuid The UUID of the department
     * @return Department|null The department instance or null if not found
     */
    public function findByUuid(string $uuid): ?Department
    {
        return Department::where('uuid', $uuid)->first();
    }

    /**
     * Create a new department
     *
     * @param array $data Department data including company_id, name
     * @return Department The created department instance
     */
    public function createDepartment(array $data): Department
    {
        return Department::create($data);
    }

    /**
     * Update an existing department
     *
     * @param Department $department The department to update
     * @param array $data The data to update
     * @return Department The updated department instance
     */
    public function updateDepartment(Department $department, array $data): Department
    {
        $department->update($data);
        return $department->fresh('company');
    }

    /**
     * Delete a department from the database
     *
     * @param Department $department The department to delete
     * @return bool True if deletion was successful
     */
    public function deleteDepartment(Department $department): bool
    {
        return $department->delete();
    }
}
