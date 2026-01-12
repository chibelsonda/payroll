<?php

namespace App\Services;

use App\Models\Attendance;

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
     * Get attendance summary with optional filters
     */
    public function getAttendanceSummary(?int $employeeId = null, ?string $from = null, ?string $to = null, ?bool $needsReview = null)
    {
        $query = Attendance::with(['employee.user'])->orderBy('date', 'desc');

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        if ($from) {
            $query->whereDate('date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('date', '<=', $to);
        }

        if ($needsReview !== null) {
            $query->where('needs_review', $needsReview);
        }

        return $query->get();
    }

    /**
     * Find an attendance record by UUID
     */
    public function findByUuid(string $uuid): ?Attendance
    {
        return Attendance::where('uuid', $uuid)->first();
    }

    /**
     * Delete an attendance record
     * Note: This only deletes the summary. Logs should be deleted separately.
     */
    public function deleteAttendance(Attendance $attendance): bool
    {
        return $attendance->delete();
    }
}
