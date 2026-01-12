<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Attendance;
use App\Services\AttendanceProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceFixController extends BaseApiController
{
    public function __construct(
        protected AttendanceProcessingService $attendanceProcessingService
    ) {}

    /**
     * Recalculate attendance summary (admin only)
     * Used when admin manually fixes logs
     */
    public function fix(Request $request, Attendance $attendance): JsonResponse
    {
        $attendance = $this->attendanceProcessingService->processAttendance(
            $attendance->employee_id,
            $attendance->date->format('Y-m-d')
        );

        return $this->successResponse(
            \App\Http\Resources\AttendanceResource::make($attendance),
            'Attendance recalculated successfully'
        );
    }
}
