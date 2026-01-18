<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreEmployeeAllowanceRequest;
use App\Http\Requests\UpdateEmployeeAllowanceRequest;
use App\Http\Resources\EmployeeAllowanceResource;
use App\Services\EmployeeAllowanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeAllowanceController extends BaseApiController
{
    public function __construct(
        protected EmployeeAllowanceService $employeeAllowanceService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $employeeAllowances = $this->employeeAllowanceService->getAllEmployeeAllowances(
            $request->query('employee_uuid')
        );

        return $this->successResponse(
            EmployeeAllowanceResource::collection($employeeAllowances),
            'Employee allowances retrieved successfully.'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeAllowanceRequest $request): JsonResponse
    {
        $employeeAllowance = $this->employeeAllowanceService->createEmployeeAllowance($request->validated());

        return $this->createdResponse(
            new EmployeeAllowanceResource($employeeAllowance),
            'Employee allowance created successfully.'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid): JsonResponse
    {
        $employeeAllowance = $this->employeeAllowanceService->findEmployeeAllowanceByUuid($uuid);

        if (!$employeeAllowance) {
            return $this->errorResponse('Employee allowance not found.', [], [], 404);
        }

        return $this->successResponse(
            new EmployeeAllowanceResource($employeeAllowance),
            'Employee allowance retrieved successfully.'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeAllowanceRequest $request, string $uuid): JsonResponse
    {
        $employeeAllowance = $this->employeeAllowanceService->findEmployeeAllowanceByUuid($uuid);

        if (!$employeeAllowance) {
            return $this->errorResponse('Employee allowance not found.', [], [], 404);
        }

        $employeeAllowance = $this->employeeAllowanceService->updateEmployeeAllowance(
            $employeeAllowance,
            $request->validated()
        );

        return $this->successResponse(
            new EmployeeAllowanceResource($employeeAllowance),
            'Employee allowance updated successfully.'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid): JsonResponse
    {
        $employeeAllowance = $this->employeeAllowanceService->findEmployeeAllowanceByUuid($uuid);

        if (!$employeeAllowance) {
            return $this->errorResponse('Employee allowance not found.', [], [], 404);
        }

        $this->employeeAllowanceService->deleteEmployeeAllowance($employeeAllowance);

        return $this->successResponse(
            null,
            'Employee allowance deleted successfully.'
        );
    }
}
