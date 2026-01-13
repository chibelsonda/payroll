<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     * Assign owner role to user for a company
     *
     * @param \App\Models\User $user The user to assign the role to
     * @param \App\Models\Company $company The company to assign the role for
     * @return void
     */
    public function assignOwnerRoleToUser(\App\Models\User $user, \App\Models\Company $company): void
    {
        $registrar = app(\Spatie\Permission\PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();

        try {
            // Set team context BEFORE assigning role
            $registrar->setPermissionsTeamId($company->id);

            // Clear permission cache to ensure fresh lookup
            $registrar->forgetCachedPermissions();

            // Remove any existing roles first (for this company)
            // Manually delete from model_has_roles table to ensure proper cleanup
            DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('model_type', 'App\Models\User')
                ->where('company_id', $company->id)
                ->delete();

            // Clear cache after removing roles
            $registrar->forgetCachedPermissions();

            // Assign 'owner' role (scoped to this company_id)
            $user->assignRole('owner');

            // Clear cache to ensure the assignment is reflected
            $registrar->forgetCachedPermissions();
        } finally {
            // Restore previous team context
            $registrar->setPermissionsTeamId($previousTeamId);
        }
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
