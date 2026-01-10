<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\PositionService;
use Illuminate\Http\JsonResponse;

class PositionController extends BaseApiController
{
    public function __construct(
        protected PositionService $positionService
    ) {}

    /**
     * Display a listing of positions (for dropdowns)
     * Supports filtering by department_uuid query parameter
     */
    public function index(): JsonResponse
    {
        $departmentUuid = request('department_uuid') ?? null;
        $positions = $this->positionService->getAllPositions($departmentUuid);

        return $this->successResponse(
            \App\Http\Resources\PositionResource::collection($positions),
            'Positions retrieved successfully'
        );
    }

}
