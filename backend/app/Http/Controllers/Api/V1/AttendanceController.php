<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;

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
     * Create a new attendance record
     */
    public function store(StoreAttendanceRequest $request): JsonResponse
    {
        $attendance = $this->attendanceService->createAttendance($request->validated());
        return $this->createdResponse(
            new AttendanceResource($attendance->load(['employee.user'])),
            'Attendance record created successfully'
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
     * Update an existing attendance record
     */
    public function update(UpdateAttendanceRequest $request, Attendance $attendance): JsonResponse
    {
        $attendance = $this->attendanceService->updateAttendance($attendance, $request->validated());
        return $this->successResponse(
            new AttendanceResource($attendance),
            'Attendance record updated successfully'
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
