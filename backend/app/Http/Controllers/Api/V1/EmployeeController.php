<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;

class EmployeeController extends BaseApiController
{

    protected EmployeeService $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    /**
     * Get all employees with pagination
     *
     * @return JsonResponse Paginated employee resources
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Employee::class);

        $employees = $this->employeeService->getAllEmployees();

        // Extract pagination metadata before wrapping in ResourceCollection
        $meta = [
            'pagination' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
                'from' => $employees->firstItem(),
                'to' => $employees->lastItem(),
                'has_more_pages' => $employees->hasMorePages(),
            ]
        ];

        return $this->successResponse(
            EmployeeResource::collection($employees->items()),
            'Employees retrieved successfully',
            $meta
        );
    }

    /**
     * Create a new employee record
     *
     * @param Request $request HTTP request with employee data
     * @return JsonResponse The created employee resource
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = $this->employeeService->createEmployee($request->validated());
        return $this->createdResponse(
            new EmployeeResource($employee),
            'Employee created successfully'
        );
    }

    /**
     * Get a specific employee with full details
     *
     * @param Employee $employee The employee instance
     * @return JsonResponse The employee resource with relationships
     */
    public function show(Employee $employee): JsonResponse
    {
        $this->authorize('view', $employee);

        $employee = $this->employeeService->getEmployeeWithDetails($employee);
        return $this->successResponse(
            new EmployeeResource($employee),
            'Employee details retrieved successfully'
        );
    }

    /**
     * Update an existing employee record
     *
     * @param UpdateEmployeeRequest $request HTTP request with update data
     * @param Employee $employee The employee to update
     * @return JsonResponse The updated employee resource
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $this->authorize('update', $employee);

        $employee = $this->employeeService->updateEmployee($employee, $request->validated());
        return $this->successResponse(
            new EmployeeResource($employee),
            'Employee updated successfully'
        );
    }

    /**
     * Delete an employee record
     *
     * @param Employee $employee The employee to delete
     * @return JsonResponse Success response with deletion message
     */
    public function destroy(Employee $employee): JsonResponse
    {
        $this->authorize('delete', $employee);

        $this->employeeService->deleteEmployee($employee);
        return $this->noContentResponse('Employee deleted successfully');
    }
}
