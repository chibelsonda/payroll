<?php

namespace App\Services;

use App\Models\Contribution;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ContributionService
{
    /**
     * Get all contributions with pagination
     */
    public function getAllContributions(): LengthAwarePaginator
    {
        return Contribution::orderBy('name')->paginate(config('application.pagination.per_page'));
    }

    /**
     * Find contribution by UUID
     */
    public function findContributionByUuid(string $uuid): ?Contribution
    {
        return Contribution::where('uuid', $uuid)->first();
    }

    /**
     * Create a new contribution
     */
    public function createContribution(array $data): Contribution
    {
        return Contribution::create($data);
    }

    /**
     * Update an existing contribution
     */
    public function updateContribution(Contribution $contribution, array $data): Contribution
    {
        $contribution->update($data);
        return $contribution->fresh();
    }

    /**
     * Delete a contribution
     */
    public function deleteContribution(Contribution $contribution): bool
    {
        return $contribution->delete();
    }
}
