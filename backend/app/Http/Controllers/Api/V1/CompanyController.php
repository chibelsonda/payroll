<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreCompanyRequest;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyController extends BaseApiController
{
    public function __construct(
        protected CompanyService $companyService
    ) {}

    /**
     * Display a listing of companies (for dropdowns)
     * Only returns the company that the authenticated user belongs to.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->successResponse(
                \App\Http\Resources\CompanyResource::collection(collect()),
                'No user found'
            );
        }

        // Return all companies the user belongs to
        $companies = $user->companies;

        return $this->successResponse(
            \App\Http\Resources\CompanyResource::collection($companies),
            'Companies retrieved successfully'
        );
    }

    /**
     * Create a new company and assign the authenticated user as owner
     * This is called during user onboarding when user has no company yet
     */
    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', [], [], 401);
        }

        return DB::transaction(function () use ($request, $user) {
            // Create the company
            $company = $this->companyService->createCompany($request->validated());

            // Attach user to the company via pivot table
            $user->companies()->syncWithoutDetaching([$company->id]);

            // Assign owner role to the user for this company
            $this->companyService->assignOwnerRoleToUser($user, $company);

            return $this->createdResponse(
                new \App\Http\Resources\CompanyResource($company),
                'Company created successfully'
            );
        });
    }

}
