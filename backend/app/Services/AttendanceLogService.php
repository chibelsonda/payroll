<?php

namespace App\Services;

use App\Models\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceLogService
{
    public function __construct(
        protected AttendanceProcessingService $attendanceProcessingService
    ) {}

    /**
     * Get attendance logs with optional filters
     */
    public function getAttendanceLogs(?int $employeeId = null, ?string $date = null)
    {
        $query = AttendanceLog::with(['employee.user'])
            ->orderBy('log_time', 'desc');

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        if ($date) {
            $query->whereDate('log_time', $date);
        }

        return $query->get();
    }

    /**
     * Create a new attendance log
     */
    public function createAttendanceLog(array $data): AttendanceLog
    {
        // Set log_time to now() if not provided
        if (!isset($data['log_time'])) {
            $data['log_time'] = now();
        }

        // Convert log_time to Carbon if it's a string
        if (is_string($data['log_time'])) {
            $data['log_time'] = Carbon::parse($data['log_time']);
        }

        $log = AttendanceLog::create($data);

        // Recalculate attendance summary for the log date using the processing service
        $this->attendanceProcessingService->processAttendance(
            $log->employee_id,
            $log->log_time->toDateString()
        );

        return $log->load(['employee.user']);
    }

    /**
     * Delete an attendance log
     */
    public function deleteAttendanceLog(AttendanceLog $log): bool
    {
        $employeeId = $log->employee_id;
        $date = $log->log_time->toDateString();

        $deleted = $log->delete();

        if ($deleted) {
            try {
                // Recalculate attendance summary after deletion using the processing service
                $this->attendanceProcessingService->processAttendance($employeeId, $date);
            } catch (\Exception $e) {
                // Log the error but don't fail the deletion
                Log::error('Failed to recalculate attendance after log deletion', [
                    'employee_id' => $employeeId,
                    'date' => $date,
                    'error' => $e->getMessage(),
                ]);
                // Still return true since the log was deleted successfully
            }
        }

        return $deleted;
    }

    /**
     * Recalculate attendance summary from logs for a specific employee and date
     * Delegates to AttendanceProcessingService for comprehensive rule processing
     */
    public function recalculateAttendanceSummary(int $employeeId, string $date): void
    {
        $this->attendanceProcessingService->processAttendance($employeeId, $date);
    }
}
