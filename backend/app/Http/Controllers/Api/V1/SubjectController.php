<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\UpdateSubjectRequest;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use App\Services\SubjectService;
use Illuminate\Http\JsonResponse;

class SubjectController extends BaseApiController
{

    protected SubjectService $subjectService;

    public function __construct(SubjectService $subjectService)
    {
        $this->subjectService = $subjectService;
    }

    /**
     * Get all subjects with pagination
     *
     * @return JsonResponse Paginated subject resources
     */
    public function index(): JsonResponse
    {
        $subjects = $this->subjectService->getAllSubjects();
        return $this->successResponse(
            SubjectResource::collection($subjects),
            'Subjects retrieved successfully'
        );
    }

    /**
     * Create a new subject record
     *
     * @param Request $request HTTP request with subject data
     * @return JsonResponse The created subject resource
     */
    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $subject = $this->subjectService->createSubject($request->validated());
        return $this->createdResponse(
            new SubjectResource($subject),
            'Subject created successfully'
        );
    }

    /**
     * Get a specific subject with full details
     *
     * @param Subject $subject The subject instance
     * @return JsonResponse The subject resource with relationships
     */
    public function show(Subject $subject): JsonResponse
    {
        $subject = $this->subjectService->getSubjectWithDetails($subject);
        return $this->successResponse(
            new SubjectResource($subject),
            'Subject details retrieved successfully'
        );
    }

    /**
     * Update an existing subject record
     *
     * @param Request $request HTTP request with update data
     * @param Subject $subject The subject to update
     * @return JsonResponse The updated subject resource
     */
    public function update(UpdateSubjectRequest $request, Subject $subject): JsonResponse
    {
        $subject = $this->subjectService->updateSubject($subject, $request->validated());
        return $this->successResponse(
            new SubjectResource($subject),
            'Subject updated successfully'
        );
    }

    /**
     * Delete a subject record
     *
     * @param Subject $subject The subject to delete
     * @return JsonResponse Success response with deletion message
     */
    public function destroy(Subject $subject): JsonResponse
    {
        $this->authorize('delete', $subject);

        $this->subjectService->deleteSubject($subject);
        return $this->noContentResponse('Subject deleted successfully');
    }
}
