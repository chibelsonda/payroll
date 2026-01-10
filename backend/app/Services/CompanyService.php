<?php

namespace App\Services;

use App\Models\Company;

class CompanyService
{
    /**
     * Get all companies ordered by name
     *
     * @return \Illuminate\Database\Eloquent\Collection Collection of companies
     */
    public function getAllCompanies()
    {
        return Company::orderBy('name')->get();
    }

    /**
     * Find a company by its UUID
     *
     * @param string $uuid The UUID of the company
     * @return Company|null The company instance or null if not found
     */
    public function findByUuid(string $uuid): ?Company
    {
        return Company::where('uuid', $uuid)->first();
    }

    /**
     * Create a new company
     *
     * @param array $data Company data including name, registration_no, address
     * @return Company The created company instance
     */
    public function createCompany(array $data): Company
    {
        return Company::create($data);
    }

    /**
     * Update an existing company
     *
     * @param Company $company The company to update
     * @param array $data The data to update
     * @return Company The updated company instance
     */
    public function updateCompany(Company $company, array $data): Company
    {
        $company->update($data);
        return $company->fresh();
    }

    /**
     * Delete a company from the database
     *
     * @param Company $company The company to delete
     * @return bool True if deletion was successful
     */
    public function deleteCompany(Company $company): bool
    {
        return $company->delete();
    }
}
