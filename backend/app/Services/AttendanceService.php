<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;

class AttendanceService
{
    /**
     * Get all attendance records with pagination
     */
    public function getAllAttendances()
    {
        return Attendance::with(['employee.user'])->orderBy('date', 'desc')->paginate(config('application.pagination.per_page'));
    }

    /**
     * Find an attendance record by UUID
     */
    public function findByUuid(string $uuid): ?Attendance
    {
        return Attendance::where('uuid', $uuid)->first();
    }

    /**
     * Create a new attendance record
     */
    public function createAttendance(array $data): Attendance
    {
        return Attendance::create($data);
    }

    /**
     * Update an existing attendance record
     */
    public function updateAttendance(Attendance $attendance, array $data): Attendance
    {
        $attendance->update($data);
        return $attendance->fresh(['employee.user']);
    }

    /**
     * Delete an attendance record
     */
    public function deleteAttendance(Attendance $attendance): bool
    {
        return $attendance->delete();
    }
}
