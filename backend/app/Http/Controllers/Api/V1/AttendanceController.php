<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends BaseApiController
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    /**
     * Get all attendance records with pagination
     */
    public function index(): JsonResponse
    {
        $attendances = $this->attendanceService->getAllAttendances();

        $meta = [
            'pagination' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
                'from' => $attendances->firstItem(),
                'to' => $attendances->lastItem(),
                'has_more_pages' => $attendances->hasMorePages(),
            ]
        ];

        return $this->successResponse(
            AttendanceResource::collection($attendances->items()),
            'Attendance records retrieved successfully',
            $meta
        );
    }

    /**
     * Get attendance summary with optional filters
     */
    public function summary(Request $request): JsonResponse
    {
        $employeeId = null;
        $from = $request->query('from');
        $to = $request->query('to');
        $needsReview = $request->has('needs_review') ? filter_var($request->query('needs_review'), FILTER_VALIDATE_BOOLEAN) : null;

        if ($request->has('employee_uuid')) {
            $employee = \App\Models\Employee::where('uuid', $request->query('employee_uuid'))->first();
            if ($employee) {
                $employeeId = $employee->id;
            }
        }

        $summary = $this->attendanceService->getAttendanceSummary($employeeId, $from, $to, $needsReview);

        return $this->successResponse(
            AttendanceResource::collection($summary),
            'Attendance summary retrieved successfully'
        );
    }

    /**
     * Get a specific attendance record
     */
    public function show(Attendance $attendance): JsonResponse
    {
        $attendance = $attendance->load(['employee.user']);
        return $this->successResponse(
            new AttendanceResource($attendance),
            'Attendance record retrieved successfully'
        );
    }

    /**
     * Delete an attendance record
     */
    public function destroy(Attendance $attendance): JsonResponse
    {
        $this->attendanceService->deleteAttendance($attendance);
        return $this->noContentResponse('Attendance record deleted successfully');
    }
}
