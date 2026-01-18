<?php

namespace App\Services;

use App\Models\Shift;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ShiftService
{
    /**
     * Get all shifts with pagination
     */
    public function getAllShifts(): LengthAwarePaginator
    {
        return Shift::with('company')
            ->orderBy('start_time', 'asc')
            ->paginate(config('application.pagination.per_page'));
    }

    /**
     * Get shifts for a specific company
     */
    public function getCompanyShifts(int $companyId): Collection
    {
        return Shift::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * Find shift by UUID
     */
    public function findShiftByUuid(string $uuid): ?Shift
    {
        return Shift::where('uuid', $uuid)->with('company')->first();
    }

    /**
     * Create a new shift
     */
    public function createShift(array $data): Shift
    {
        return Shift::create($data);
    }

    /**
     * Update an existing shift
     */
    public function updateShift(Shift $shift, array $data): Shift
    {
        $shift->update($data);
        return $shift->fresh(['company']);
    }

    /**
     * Delete a shift
     */
    public function deleteShift(Shift $shift): bool
    {
        return $shift->delete();
    }
}
