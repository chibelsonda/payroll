<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends BaseApiController
{

    protected EnrollmentService $enrollmentService;

    public function __construct(EnrollmentService $enrollmentService)
    {
        $this->enrollmentService = $enrollmentService;
    }

    /**
     * Get enrollments for the current user (admin sees all, students see their own)
     *
     * @param Request $request HTTP request
     * @return JsonResponse Paginated enrollment resources
     */
    public function index(Request $request): JsonResponse
    {
        $enrollments = $this->enrollmentService->getEnrollmentsForUser($request->user());
        return $this->successResponse(
            EnrollmentResource::collection($enrollments),
            'Enrollments retrieved successfully'
        );
    }

    /**
     * Enroll the current user (student) in a subject
     *
     * @param Request $request HTTP request with subject_id
     * @return JsonResponse The created enrollment resource
     */
    public function store(StoreEnrollmentRequest $request): JsonResponse
    {
        $enrollment = $this->enrollmentService->enrollStudent($request->user(), $request->validated()['subject_id']);
        return $this->createdResponse(
            new EnrollmentResource($enrollment),
            'Student enrolled successfully'
        );
    }

    /**
     * Get a specific enrollment with full details
     *
     * @param Enrollment $enrollment The enrollment instance
     * @return JsonResponse The enrollment resource with relationships
     */
    public function show(Enrollment $enrollment): JsonResponse
    {
        $this->authorize('view', $enrollment);

        $enrollment = $this->enrollmentService->getEnrollmentWithDetails($enrollment);
        return $this->successResponse(
            new EnrollmentResource($enrollment),
            'Enrollment details retrieved successfully'
        );
    }

    /**
     * Update an existing enrollment record
     *
     * @param Request $request HTTP request with update data
     * @param Enrollment $enrollment The enrollment to update
     * @return JsonResponse The updated enrollment resource
     */
    public function update(UpdateEnrollmentRequest $request, Enrollment $enrollment): JsonResponse
    {
        $enrollment = $this->enrollmentService->updateEnrollment($enrollment, $request->validated());
        return $this->successResponse(
            new EnrollmentResource($enrollment),
            'Enrollment updated successfully'
        );
    }

    /**
     * Delete an enrollment record
     *
     * @param Enrollment $enrollment The enrollment to delete
     * @return JsonResponse Success response with deletion message
     */
    public function destroy(Enrollment $enrollment): JsonResponse
    {
        $this->authorize('delete', $enrollment);

        $this->enrollmentService->deleteEnrollment($enrollment);
        return $this->noContentResponse('Enrollment deleted successfully');
    }
}
