<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Attendance;
use App\Services\AttendanceProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceResolveController extends BaseApiController
{
    public function __construct(
        protected AttendanceProcessingService $attendanceProcessingService
    ) {}

    /**
     * Resolve attendance (admin only)
     * Marks attendance as resolved and removes review flags
     */
    public function resolve(Request $request, Attendance $attendance): JsonResponse
    {
        $attendance = $this->attendanceProcessingService->resolveAttendance($attendance);

        return $this->successResponse(
            \App\Http\Resources\AttendanceResource::make($attendance),
            'Attendance resolved successfully'
        );
    }
}
