<?php

namespace App\Services;

use App\Models\Deduction;

class DeductionService
{
    /**
     * Get all deductions
     */
    public function getAllDeductions()
    {
        return Deduction::orderBy('name')->paginate(config('application.pagination.per_page'));
    }

    /**
     * Find a deduction by UUID
     */
    public function findByUuid(string $uuid): ?Deduction
    {
        return Deduction::where('uuid', $uuid)->first();
    }

    /**
     * Create a new deduction
     */
    public function createDeduction(array $data): Deduction
    {
        return Deduction::create($data);
    }

    /**
     * Update an existing deduction
     */
    public function updateDeduction(Deduction $deduction, array $data): Deduction
    {
        $deduction->update($data);
        return $deduction->fresh();
    }

    /**
     * Delete a deduction
     */
    public function deleteDeduction(Deduction $deduction): bool
    {
        return $deduction->delete();
    }
}
