<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Department;
use Illuminate\Http\JsonResponse;

class DepartmentController extends BaseApiController
{
    /**
     * Display a listing of departments (for dropdowns)
     * Supports filtering by company_uuid query parameter
     */
    public function index(): JsonResponse
    {
        $query = Department::query()->with('company');

        if (request()->has('company_uuid') && request('company_uuid')) {
            $company = \App\Models\Company::where('uuid', request('company_uuid'))->first();
            if ($company) {
                $query->where('company_id', $company->id);
            } else {
                // Return empty result if company not found
                return $this->successResponse([], 'Departments retrieved successfully');
            }
        }

        $departments = $query->orderBy('name')->get();
        return $this->successResponse(
            \App\Http\Resources\DepartmentResource::collection($departments),
            'Departments retrieved successfully'
        );
    }

}
