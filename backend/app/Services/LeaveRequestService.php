<?php

namespace App\Services;

use App\Models\LeaveRequest;

class LeaveRequestService
{
    /**
     * Get all leave requests with pagination
     */
    public function getAllLeaveRequests()
    {
        return LeaveRequest::with(['employee.user'])->orderBy('created_at', 'desc')->paginate(config('application.pagination.per_page'));
    }

    /**
     * Find a leave request by UUID
     */
    public function findByUuid(string $uuid): ?LeaveRequest
    {
        return LeaveRequest::where('uuid', $uuid)->first();
    }

    /**
     * Create a new leave request
     */
    public function createLeaveRequest(array $data): LeaveRequest
    {
        $data['status'] = 'pending';
        return LeaveRequest::create($data);
    }

    /**
     * Approve a leave request
     */
    public function approveLeaveRequest(LeaveRequest $leaveRequest): LeaveRequest
    {
        $leaveRequest->update(['status' => 'approved']);
        return $leaveRequest->fresh(['employee.user']);
    }

    /**
     * Reject a leave request
     */
    public function rejectLeaveRequest(LeaveRequest $leaveRequest): LeaveRequest
    {
        $leaveRequest->update(['status' => 'rejected']);
        return $leaveRequest->fresh(['employee.user']);
    }

    /**
     * Delete a leave request
     */
    public function deleteLeaveRequest(LeaveRequest $leaveRequest): bool
    {
        return $leaveRequest->delete();
    }
}
