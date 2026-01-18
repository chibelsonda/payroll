<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreShiftRequest;
use App\Http\Requests\UpdateShiftRequest;
use App\Http\Resources\ShiftResource;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;

class ShiftController extends BaseApiController
{
    public function __construct(
        protected ShiftService $shiftService
    ) {}

    /**
     * Get all shifts
     */
    public function index(): JsonResponse
    {
        $shifts = $this->shiftService->getAllShifts();

        $meta = [
            'pagination' => [
                'current_page' => $shifts->currentPage(),
                'last_page' => $shifts->lastPage(),
                'per_page' => $shifts->perPage(),
                'total' => $shifts->total(),
                'from' => $shifts->firstItem(),
                'to' => $shifts->lastItem(),
                'has_more_pages' => $shifts->hasMorePages(),
            ]
        ];

        return $this->successResponse(
            ShiftResource::collection($shifts->items()),
            'Shifts retrieved successfully',
            $meta
        );
    }

    /**
     * Create a new shift
     */
    public function store(StoreShiftRequest $request): JsonResponse
    {
        $companyId = app('active_company_id');
        $data = $request->validated();
        $data['company_id'] = $companyId;

        $shift = $this->shiftService->createShift($data);

        return $this->createdResponse(
            new ShiftResource($shift->load('company')),
            'Shift created successfully'
        );
    }

    /**
     * Get a single shift by UUID
     */
    public function show(string $uuid): JsonResponse
    {
        $shift = $this->shiftService->findShiftByUuid($uuid);

        if (!$shift) {
            return $this->errorResponse('Shift not found', [], [], 404);
        }

        return $this->successResponse(
            new ShiftResource($shift),
            'Shift retrieved successfully'
        );
    }

    /**
     * Update a shift
     */
    public function update(UpdateShiftRequest $request, string $uuid): JsonResponse
    {
        $shift = $this->shiftService->findShiftByUuid($uuid);

        if (!$shift) {
            return $this->errorResponse('Shift not found', [], [], 404);
        }

        $shift = $this->shiftService->updateShift($shift, $request->validated());

        return $this->successResponse(
            new ShiftResource($shift),
            'Shift updated successfully'
        );
    }

    /**
     * Delete a shift
     */
    public function destroy(string $uuid): JsonResponse
    {
        $shift = $this->shiftService->findShiftByUuid($uuid);

        if (!$shift) {
            return $this->errorResponse('Shift not found', [], [], 404);
        }

        $this->shiftService->deleteShift($shift);

        return $this->successResponse(
            null,
            'Shift deleted successfully'
        );
    }
}
