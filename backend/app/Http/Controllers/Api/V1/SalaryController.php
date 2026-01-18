<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreSalaryRequest;
use App\Http\Requests\UpdateSalaryRequest;
use App\Http\Resources\SalaryResource;
use App\Models\Employee;
use App\Models\Salary;
use App\Services\SalaryService;
use Illuminate\Http\JsonResponse;

class SalaryController extends BaseApiController
{
    public function __construct(
        protected SalaryService $salaryService
    ) {}

    /**
     * Get all salaries with pagination
     */
    public function index(): JsonResponse
    {
        $salaries = $this->salaryService->getAllSalaries();

        $meta = [
            'pagination' => [
                'current_page' => $salaries->currentPage(),
                'last_page' => $salaries->lastPage(),
                'per_page' => $salaries->perPage(),
                'total' => $salaries->total(),
                'from' => $salaries->firstItem(),
                'to' => $salaries->lastItem(),
                'has_more_pages' => $salaries->hasMorePages(),
            ]
        ];

        return $this->successResponse(
            SalaryResource::collection($salaries->items()),
            'Salaries retrieved successfully',
            $meta
        );
    }

    /**
     * Create a new salary record
     * Note: Always creates a new record, never updates existing
     */
    public function store(StoreSalaryRequest $request): JsonResponse
    {
        $salary = $this->salaryService->createSalary($request->validated());
        return $this->createdResponse(
            new SalaryResource($salary->load(['employee.user'])),
            'Salary created successfully'
        );
    }

    /**
     * Get salary history for a specific employee
     */
    public function employeeSalaries(string $employeeUuid): JsonResponse
    {
        $salaries = $this->salaryService->getEmployeeSalaries($employeeUuid);
        return $this->successResponse(
            SalaryResource::collection($salaries),
            'Employee salary history retrieved successfully'
        );
    }

    /**
     * Get a specific salary
     */
    public function show(Salary $salary): JsonResponse
    {
        $salary = $salary->load(['employee.user']);
        return $this->successResponse(
            new SalaryResource($salary),
            'Salary retrieved successfully'
        );
    }

    /**
     * Update an existing salary
     * Note: In practice, salary changes should create new records
     */
    public function update(UpdateSalaryRequest $request, Salary $salary): JsonResponse
    {
        $salary = $this->salaryService->updateSalary($salary, $request->validated());
        return $this->successResponse(
            new SalaryResource($salary),
            'Salary updated successfully'
        );
    }

    /**
     * Delete a salary record
     */
    public function destroy(Salary $salary): JsonResponse
    {
        $this->salaryService->deleteSalary($salary);
        return $this->noContentResponse('Salary deleted successfully');
    }
}
