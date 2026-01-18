<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreCorrectionRequestRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use Illuminate\Http\JsonResponse;

class AttendanceCorrectionRequestController extends BaseApiController
{
    /**
     * Employee requests correction for attendance
     * Marks attendance.needs_review = true and stores employee explanation
     */
    public function store(StoreCorrectionRequestRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $attendance = Attendance::findOrFail($validated['attendance_id']);

        // Check if attendance is locked
        if ($attendance->is_locked) {
            return $this->forbiddenResponse('Attendance record is locked and cannot be modified');
        }

        // Update attendance with correction request
        $attendance->update([
            'needs_review' => true,
            'correction_reason' => $validated['reason'],
        ]);

        return $this->successResponse(
            new AttendanceResource($attendance->fresh(['employee.user'])),
            'Correction request submitted successfully'
        );
    }
}
