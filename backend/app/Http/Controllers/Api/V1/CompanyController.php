<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;

class CompanyController extends BaseApiController
{
    public function __construct(
        protected CompanyService $companyService
    ) {}

    /**
     * Display a listing of companies (for dropdowns)
     */
    public function index(): JsonResponse
    {
        $companies = $this->companyService->getAllCompanies();
        return $this->successResponse(
            \App\Http\Resources\CompanyResource::collection($companies),
            'Companies retrieved successfully'
        );
    }

}
