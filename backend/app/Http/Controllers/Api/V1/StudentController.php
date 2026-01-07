<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;

class StudentController extends BaseApiController
{

    protected StudentService $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    /**
     * Get all students with pagination
     *
     * @return JsonResponse Paginated student resources
     */
    public function index(): JsonResponse
    {
        $students = $this->studentService->getAllStudents();
        return $this->successResponse(
            StudentResource::collection($students),
            'Students retrieved successfully'
        );
    }

    /**
     * Create a new student record
     *
     * @param Request $request HTTP request with student data
     * @return JsonResponse The created student resource
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        $student = $this->studentService->createStudent($request->validated());
        return $this->createdResponse(
            new StudentResource($student),
            'Student created successfully'
        );
    }

    /**
     * Get a specific student with full details
     *
     * @param Student $student The student instance
     * @return JsonResponse The student resource with relationships
     */
    public function show(Student $student): JsonResponse
    {
        $this->authorize('view', $student);

        $student = $this->studentService->getStudentWithDetails($student);
        return $this->successResponse(
            new StudentResource($student),
            'Student details retrieved successfully'
        );
    }

    /**
     * Update an existing student record
     *
     * @param Request $request HTTP request with update data
     * @param Student $student The student to update
     * @return JsonResponse The updated student resource
     */
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $student = $this->studentService->updateStudent($student, $request->validated());
        return $this->successResponse(
            new StudentResource($student),
            'Student updated successfully'
        );
    }

    /**
     * Delete a student record
     *
     * @param Student $student The student to delete
     * @return JsonResponse Success response with deletion message
     */
    public function destroy(Student $student): JsonResponse
    {
        $this->authorize('delete', $student);

        $this->studentService->deleteStudent($student);
        return $this->noContentResponse('Student deleted successfully');
    }
}
