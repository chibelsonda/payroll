<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\UpdateAttendanceLogRequest;
use App\Http\Resources\AttendanceLogResource;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Services\AttendanceProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceManageController extends BaseApiController
{
    public function __construct(
        protected AttendanceProcessingService $attendanceProcessingService
    ) {}

    /**
     * Update attendance log (admin only)
     * Allowed only if attendance not locked
     */
    public function updateLog(UpdateAttendanceLogRequest $request, AttendanceLog $attendanceLog): JsonResponse
    {
        // Get the attendance record for this log
        $attendance = Attendance::where('employee_id', $attendanceLog->employee_id)
            ->whereDate('date', $attendanceLog->log_time->toDateString())
            ->first();

        if ($attendance && $attendance->is_locked) {
            return $this->forbiddenResponse('Attendance record is locked and cannot be modified');
        }

        // Update the log
        $attendanceLog->update($request->validated());

        // Recalculate attendance if it exists
        if ($attendance) {
            $this->attendanceProcessingService->processAttendance(
                $attendanceLog->employee_id,
                $attendanceLog->log_time->toDateString()
            );
        }

        return $this->successResponse(
            new AttendanceLogResource($attendanceLog->fresh(['employee.user'])),
            'Attendance log updated successfully'
        );
    }

    /**
     * Recalculate attendance summary (admin only)
     */
    public function recalculate(Request $request): JsonResponse
    {
        $request->validate([
            'attendance_uuid' => 'required|string|exists:attendance,uuid',
        ]);

        $attendance = Attendance::where('uuid', $request->attendance_uuid)->firstOrFail();

        $attendance = $this->attendanceProcessingService->processAttendance(
            $attendance->employee_id,
            $attendance->date->format('Y-m-d')
        );

        return $this->successResponse(
            new AttendanceResource($attendance),
            'Attendance recalculated successfully'
        );
    }

    /**
     * Approve auto-corrected attendance (admin only)
     */
    public function approve(Request $request): JsonResponse
    {
        $request->validate([
            'attendance_uuid' => 'required|string|exists:attendance,uuid',
        ]);

        $attendance = Attendance::where('uuid', $request->attendance_uuid)->firstOrFail();

        if ($attendance->is_locked) {
            return $this->forbiddenResponse('Attendance record is locked and cannot be modified');
        }

        $attendance->update([
            'needs_review' => false,
            'is_auto_corrected' => false,
        ]);

        return $this->successResponse(
            new AttendanceResource($attendance->fresh(['employee.user'])),
            'Attendance approved successfully'
        );
    }

    /**
     * Mark attendance as incomplete (admin only)
     */
    public function markIncomplete(Request $request): JsonResponse
    {
        $request->validate([
            'attendance_uuid' => 'required|string|exists:attendance,uuid',
        ]);

        $attendance = Attendance::where('uuid', $request->attendance_uuid)->firstOrFail();

        if ($attendance->is_locked) {
            return $this->forbiddenResponse('Attendance record is locked and cannot be modified');
        }

        $attendance->update([
            'status' => 'incomplete',
            'needs_review' => true,
        ]);

        return $this->successResponse(
            new AttendanceResource($attendance->fresh(['employee.user'])),
            'Attendance marked as incomplete'
        );
    }

    /**
     * Lock attendance (admin only)
     * Prevents further edits - used before payroll processing
     */
    public function lock(Request $request): JsonResponse
    {
        $request->validate([
            'attendance_uuid' => 'required|string|exists:attendance,uuid',
        ]);

        $attendance = Attendance::where('uuid', $request->attendance_uuid)->firstOrFail();

        $attendance->update([
            'is_locked' => true,
        ]);

        return $this->successResponse(
            new AttendanceResource($attendance->fresh(['employee.user'])),
            'Attendance locked successfully'
        );
    }
}
