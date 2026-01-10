<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Position;

class PositionService
{
    /**
     * Get all positions, optionally filtered by department UUID
     *
     * @param string|null $departmentUuid Optional UUID of the department to filter by
     * @return \Illuminate\Database\Eloquent\Collection Collection of positions
     */
    public function getAllPositions(?string $departmentUuid = null)
    {
        $query = Position::query()->with('department');

        if ($departmentUuid) {
            $department = Department::where('uuid', $departmentUuid)->first();
            if ($department) {
                $query->where('department_id', $department->id);
            } else {
                // Return empty collection if department not found
                return collect([]);
            }
        }

        return $query->orderBy('title')->get();
    }

    /**
     * Find a position by its UUID
     *
     * @param string $uuid The UUID of the position
     * @return Position|null The position instance or null if not found
     */
    public function findByUuid(string $uuid): ?Position
    {
        return Position::where('uuid', $uuid)->first();
    }

    /**
     * Create a new position
     *
     * @param array $data Position data including department_id, title
     * @return Position The created position instance
     */
    public function createPosition(array $data): Position
    {
        return Position::create($data);
    }

    /**
     * Update an existing position
     *
     * @param Position $position The position to update
     * @param array $data The data to update
     * @return Position The updated position instance
     */
    public function updatePosition(Position $position, array $data): Position
    {
        $position->update($data);
        return $position->fresh('department');
    }

    /**
     * Delete a position from the database
     *
     * @param Position $position The position to delete
     * @return bool True if deletion was successful
     */
    public function deletePosition(Position $position): bool
    {
        return $position->delete();
    }
}
