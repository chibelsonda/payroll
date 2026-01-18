<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreLeaveRequestRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use Illuminate\Http\JsonResponse;

class LeaveRequestController extends BaseApiController
{
    public function __construct(
        protected LeaveRequestService $leaveRequestService
    ) {}

    /**
     * Get all leave requests with pagination
     */
    public function index(): JsonResponse
    {
        $leaveRequests = $this->leaveRequestService->getAllLeaveRequests();

        $meta = [
            'pagination' => [
                'current_page' => $leaveRequests->currentPage(),
                'last_page' => $leaveRequests->lastPage(),
                'per_page' => $leaveRequests->perPage(),
                'total' => $leaveRequests->total(),
                'from' => $leaveRequests->firstItem(),
                'to' => $leaveRequests->lastItem(),
                'has_more_pages' => $leaveRequests->hasMorePages(),
            ]
        ];

        return $this->successResponse(
            LeaveRequestResource::collection($leaveRequests->items()),
            'Leave requests retrieved successfully',
            $meta
        );
    }

    /**
     * Create a new leave request
     */
    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        $leaveRequest = $this->leaveRequestService->createLeaveRequest($request->validated());
        return $this->createdResponse(
            new LeaveRequestResource($leaveRequest->load(['employee.user'])),
            'Leave request created successfully'
        );
    }

    /**
     * Get a specific leave request
     */
    public function show(LeaveRequest $leaveRequest): JsonResponse
    {
        $leaveRequest = $leaveRequest->load(['employee.user']);
        return $this->successResponse(
            new LeaveRequestResource($leaveRequest),
            'Leave request retrieved successfully'
        );
    }

    /**
     * Approve a leave request
     */
    public function approve(LeaveRequest $leaveRequest): JsonResponse
    {
        $leaveRequest = $this->leaveRequestService->approveLeaveRequest($leaveRequest);
        return $this->successResponse(
            new LeaveRequestResource($leaveRequest),
            'Leave request approved successfully'
        );
    }

    /**
     * Reject a leave request
     */
    public function reject(LeaveRequest $leaveRequest): JsonResponse
    {
        $leaveRequest = $this->leaveRequestService->rejectLeaveRequest($leaveRequest);
        return $this->successResponse(
            new LeaveRequestResource($leaveRequest),
            'Leave request rejected successfully'
        );
    }

    /**
     * Delete a leave request
     */
    public function destroy(LeaveRequest $leaveRequest): JsonResponse
    {
        $this->leaveRequestService->deleteLeaveRequest($leaveRequest);
        return $this->noContentResponse('Leave request deleted successfully');
    }
}
