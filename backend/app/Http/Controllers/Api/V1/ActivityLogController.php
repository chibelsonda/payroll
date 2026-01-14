<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends BaseApiController
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ActivityLog::query()
            ->with('user')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->has('subject_type')) {
            $query->where('subject_type', $request->input('subject_type'));
        }

        $perPage = $request->input('per_page', 15);
        $activityLogs = $query->paginate($perPage);

        $meta = [
            'pagination' => [
                'current_page' => $activityLogs->currentPage(),
                'last_page' => $activityLogs->lastPage(),
                'per_page' => $activityLogs->perPage(),
                'total' => $activityLogs->total(),
                'from' => $activityLogs->firstItem(),
                'to' => $activityLogs->lastItem(),
            ],
        ];

        return $this->successResponse(
            ActivityLogResource::collection($activityLogs->items()),
            'Activity logs retrieved successfully.',
            $meta
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $activityLog = ActivityLog::with('user')->find($id);

        if (!$activityLog) {
            return $this->errorResponse('Activity log not found.', [], [], 404);
        }

        return $this->successResponse(
            new ActivityLogResource($activityLog),
            'Activity log retrieved successfully.'
        );
    }
}
