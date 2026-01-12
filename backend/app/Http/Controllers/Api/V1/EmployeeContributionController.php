<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreEmployeeContributionRequest;
use App\Http\Resources\EmployeeContributionResource;
use App\Services\EmployeeContributionService;
use Illuminate\Http\JsonResponse;

class EmployeeContributionController extends BaseApiController
{
    public function __construct(
        protected EmployeeContributionService $employeeContributionService
    ) {}

    /**
     * Get all contributions for a specific employee
     */
    public function index(string $employeeUuid): JsonResponse
    {
        $employeeContributions = $this->employeeContributionService->getEmployeeContributions($employeeUuid);
        return $this->successResponse(
            EmployeeContributionResource::collection($employeeContributions),
            'Employee contributions retrieved successfully'
        );
    }

    /**
     * Assign a contribution to an employee
     */
    public function store(StoreEmployeeContributionRequest $request): JsonResponse
    {
        $employeeContribution = $this->employeeContributionService->assignContribution($request->validated());
        return $this->createdResponse(
            new EmployeeContributionResource($employeeContribution),
            'Contribution assigned to employee successfully'
        );
    }

    /**
     * Remove a contribution from an employee
     */
    public function destroy(string $employeeUuid, string $contributionUuid): JsonResponse
    {
        $this->employeeContributionService->removeContribution($employeeUuid, $contributionUuid);
        return $this->noContentResponse('Contribution removed from employee successfully');
    }
}
