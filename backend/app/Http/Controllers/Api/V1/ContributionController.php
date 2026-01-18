<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreContributionRequest;
use App\Http\Requests\UpdateContributionRequest;
use App\Http\Resources\ContributionResource;
use App\Models\Contribution;
use App\Services\ContributionService;
use Illuminate\Http\JsonResponse;

class ContributionController extends BaseApiController
{
    public function __construct(
        protected ContributionService $contributionService
    ) {}

    /**
     * Get all contributions with pagination
     */
    public function index(): JsonResponse
    {
        $contributions = $this->contributionService->getAllContributions();

        $meta = [
            'pagination' => [
                'current_page' => $contributions->currentPage(),
                'last_page' => $contributions->lastPage(),
                'per_page' => $contributions->perPage(),
                'total' => $contributions->total(),
                'from' => $contributions->firstItem(),
                'to' => $contributions->lastItem(),
                'has_more_pages' => $contributions->hasMorePages(),
            ]
        ];

        return $this->successResponse(
            ContributionResource::collection($contributions->items()),
            'Contributions retrieved successfully',
            $meta
        );
    }

    /**
     * Create a new contribution
     */
    public function store(StoreContributionRequest $request): JsonResponse
    {
        $contribution = $this->contributionService->createContribution($request->validated());
        return $this->createdResponse(
            new ContributionResource($contribution),
            'Contribution created successfully'
        );
    }

    /**
     * Get a specific contribution
     */
    public function show(Contribution $contribution): JsonResponse
    {
        return $this->successResponse(
            new ContributionResource($contribution),
            'Contribution retrieved successfully'
        );
    }

    /**
     * Update an existing contribution
     */
    public function update(UpdateContributionRequest $request, Contribution $contribution): JsonResponse
    {
        $contribution = $this->contributionService->updateContribution($contribution, $request->validated());
        return $this->successResponse(
            new ContributionResource($contribution),
            'Contribution updated successfully'
        );
    }

    /**
     * Delete a contribution
     */
    public function destroy(Contribution $contribution): JsonResponse
    {
        $this->contributionService->deleteContribution($contribution);
        return $this->noContentResponse('Contribution deleted successfully');
    }
}
