<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreCompanyRequest;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

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

        if (!$user || !$user->company_id) {
            return $this->successResponse(
                \App\Http\Resources\CompanyResource::collection(collect()),
                'No company found for user'
            );
        }

        // Return the user's company
        $company = \App\Models\Company::where('id', $user->company_id)->first();

        if (!$company) {
            return $this->successResponse(
                \App\Http\Resources\CompanyResource::collection(collect()),
                'Company not found'
            );
        }

        return $this->successResponse(
            \App\Http\Resources\CompanyResource::collection(collect([$company])),
            'Company retrieved successfully'
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

        // User should not already belong to a company
        if ($user->company_id) {
            return $this->errorResponse('User already belongs to a company', [], [], 403);
        }

        return DB::transaction(function () use ($request, $user) {
            // Create the company
            $company = $this->companyService->createCompany($request->validated());

            // Assign user to the company
            $user->company_id = $company->id;
            $user->save();

            // Set Spatie team context to this company_id
            $registrar = app(PermissionRegistrar::class);
            $previousTeamId = $registrar->getPermissionsTeamId();

            try {
                $registrar->setPermissionsTeamId($company->id);
                // Remove any existing roles first
                $user->roles()->where('company_id', $company->id)->detach();
                // Assign 'owner' role (scoped to this company_id)
                $user->assignRole('owner');
            } finally {
                $registrar->setPermissionsTeamId($previousTeamId);
            }

            return $this->createdResponse(
                new \App\Http\Resources\CompanyResource($company),
                'Company created successfully'
            );
        });
    }

}
