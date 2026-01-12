<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreDeductionRequest;
use App\Http\Requests\UpdateDeductionRequest;
use App\Http\Resources\DeductionResource;
use App\Models\Deduction;
use App\Services\DeductionService;
use Illuminate\Http\JsonResponse;

class DeductionController extends BaseApiController
{
    public function __construct(
        protected DeductionService $deductionService
    ) {}

    /**
     * Get all deductions with pagination
     */
    public function index(): JsonResponse
    {
        $deductions = $this->deductionService->getAllDeductions();

        $meta = [
            'pagination' => [
                'current_page' => $deductions->currentPage(),
                'last_page' => $deductions->lastPage(),
                'per_page' => $deductions->perPage(),
                'total' => $deductions->total(),
                'from' => $deductions->firstItem(),
                'to' => $deductions->lastItem(),
                'has_more_pages' => $deductions->hasMorePages(),
            ]
        ];

        return $this->successResponse(
            DeductionResource::collection($deductions->items()),
            'Deductions retrieved successfully',
            $meta
        );
    }

    /**
     * Create a new deduction
     */
    public function store(StoreDeductionRequest $request): JsonResponse
    {
        $deduction = $this->deductionService->createDeduction($request->validated());
        return $this->createdResponse(
            new DeductionResource($deduction),
            'Deduction created successfully'
        );
    }

    /**
     * Get a specific deduction
     */
    public function show(Deduction $deduction): JsonResponse
    {
        return $this->successResponse(
            new DeductionResource($deduction),
            'Deduction retrieved successfully'
        );
    }

    /**
     * Update an existing deduction
     */
    public function update(UpdateDeductionRequest $request, Deduction $deduction): JsonResponse
    {
        $deduction = $this->deductionService->updateDeduction($deduction, $request->validated());
        return $this->successResponse(
            new DeductionResource($deduction),
            'Deduction updated successfully'
        );
    }

    /**
     * Delete a deduction
     */
    public function destroy(Deduction $deduction): JsonResponse
    {
        $this->deductionService->deleteDeduction($deduction);
        return $this->noContentResponse('Deduction deleted successfully');
    }
}
