<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreAttendanceLogRequest;
use App\Http\Resources\AttendanceLogResource;
use App\Models\AttendanceLog;
use App\Services\AttendanceLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceLogController extends BaseApiController
{
    public function __construct(
        protected AttendanceLogService $attendanceLogService
    ) {}

    /**
     * Get attendance logs with optional filters
     */
    public function index(Request $request): JsonResponse
    {
        $employeeId = null;
        $date = $request->query('date');

        if ($request->has('employee_uuid')) {
            $employee = \App\Models\Employee::where('uuid', $request->query('employee_uuid'))->first();
            if ($employee) {
                $employeeId = $employee->id;
            }
        }

        $logs = $this->attendanceLogService->getAttendanceLogs($employeeId, $date);

        return $this->successResponse(
            AttendanceLogResource::collection($logs),
            'Attendance logs retrieved successfully'
        );
    }

    /**
     * Create a new attendance log
     */
    public function store(StoreAttendanceLogRequest $request): JsonResponse
    {
        $log = $this->attendanceLogService->createAttendanceLog($request->validated());
        return $this->createdResponse(
            new AttendanceLogResource($log),
            'Attendance log created successfully'
        );
    }

    /**
     * Delete an attendance log (admin only)
     */
    public function destroy(AttendanceLog $log): JsonResponse
    {
        try {
            $this->attendanceLogService->deleteAttendanceLog($log);
            return $this->noContentResponse('Attendance log deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete attendance log: ' . $e->getMessage(),
                [],
                [],
                500
            );
        }
    }
}
