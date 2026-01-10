<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\DepartmentService;
use Illuminate\Http\JsonResponse;

class DepartmentController extends BaseApiController
{
    public function __construct(
        protected DepartmentService $departmentService
    ) {}

    /**
     * Display a listing of departments (for dropdowns)
     * Supports filtering by company_uuid query parameter
     */
    public function index(): JsonResponse
    {
        $companyUuid = request('company_uuid') ?? null;
        $departments = $this->departmentService->getAllDepartments($companyUuid);

        return $this->successResponse(
            \App\Http\Resources\DepartmentResource::collection($departments),
            'Departments retrieved successfully'
        );
    }

}
