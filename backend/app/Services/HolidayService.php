<?php

namespace App\Services;

use App\Models\Holiday;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class HolidayService
{
    /**
     * Get all holidays with pagination
     */
    public function getAllHolidays(): LengthAwarePaginator
    {
        return Holiday::with('company')
            ->orderBy('date', 'asc')
            ->paginate(config('application.pagination.per_page'));
    }

    /**
     * Get holidays for a specific company
     */
    public function getCompanyHolidays(int $companyId): Collection
    {
        return Holiday::where('company_id', $companyId)
            ->orderBy('date', 'asc')
            ->get();
    }

    /**
     * Find holiday by UUID
     */
    public function findHolidayByUuid(string $uuid): ?Holiday
    {
        return Holiday::where('uuid', $uuid)->with('company')->first();
    }

    /**
     * Create a new holiday
     */
    public function createHoliday(array $data): Holiday
    {
        return Holiday::create($data);
    }

    /**
     * Update an existing holiday
     */
    public function updateHoliday(Holiday $holiday, array $data): Holiday
    {
        $holiday->update($data);
        return $holiday->fresh(['company']);
    }

    /**
     * Delete a holiday
     */
    public function deleteHoliday(Holiday $holiday): bool
    {
        return $holiday->delete();
    }

    /**
     * Get holidays within a date range
     */
    public function getHolidaysInRange(int $companyId, string $startDate, string $endDate): Collection
    {
        return Holiday::where('company_id', $companyId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();
    }
}
