<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

class CompanyController extends BaseApiController
{
    /**
     * Display a listing of companies (for dropdowns)
     */
    public function index(): JsonResponse
    {
        $companies = Company::orderBy('name')->get();
        return $this->successResponse(
            \App\Http\Resources\CompanyResource::collection($companies),
            'Companies retrieved successfully'
        );
    }

}
