<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreEmployeeDeductionRequest;
use App\Http\Resources\EmployeeDeductionResource;
use App\Models\Employee;
use App\Services\EmployeeDeductionService;
use Illuminate\Http\JsonResponse;

class EmployeeDeductionController extends BaseApiController
{
    public function __construct(
        protected EmployeeDeductionService $employeeDeductionService
    ) {}

    /**
     * Get all deductions for a specific employee
     */
    public function index(string $employeeUuid): JsonResponse
    {
        $employeeDeductions = $this->employeeDeductionService->getEmployeeDeductions($employeeUuid);
        return $this->successResponse(
            EmployeeDeductionResource::collection($employeeDeductions),
            'Employee deductions retrieved successfully'
        );
    }

    /**
     * Assign a deduction to an employee
     */
    public function store(StoreEmployeeDeductionRequest $request): JsonResponse
    {
        $employeeDeduction = $this->employeeDeductionService->assignDeduction($request->validated());
        return $this->createdResponse(
            new EmployeeDeductionResource($employeeDeduction),
            'Deduction assigned to employee successfully'
        );
    }

    /**
     * Remove a deduction from an employee
     */
    public function destroy(string $employeeUuid, string $deductionUuid): JsonResponse
    {
        $this->employeeDeductionService->removeDeduction($employeeUuid, $deductionUuid);
        return $this->noContentResponse('Deduction removed from employee successfully');
    }
}
