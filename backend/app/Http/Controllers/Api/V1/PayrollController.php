<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StorePayrollRunRequest;
use App\Http\Resources\PayrollResource;
use App\Http\Resources\PayrollRunResource;
use App\Services\PayrollService;
use App\Exports\PayrollRunExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PayrollController extends BaseApiController
{
    public function __construct(
        protected PayrollService $payrollService
    ) {}

    /**
     * Get all payroll runs
     */
    public function index(): JsonResponse
    {
        $payrollRuns = $this->payrollService->getAllPayrollRuns();

        $meta = [
            'pagination' => [
                'current_page' => $payrollRuns->currentPage(),
                'last_page' => $payrollRuns->lastPage(),
                'per_page' => $payrollRuns->perPage(),
                'total' => $payrollRuns->total(),
                'from' => $payrollRuns->firstItem(),
                'to' => $payrollRuns->lastItem(),
                'has_more_pages' => $payrollRuns->hasMorePages(),
            ]
        ];

        return $this->successResponse(
            PayrollRunResource::collection($payrollRuns->items()),
            'Payroll runs retrieved successfully',
            $meta
        );
    }

    /**
     * Create a new payroll run
     */
    public function store(StorePayrollRunRequest $request): JsonResponse
    {
        $payrollRun = $this->payrollService->createPayrollRun($request->validated());
        return $this->createdResponse(
            new PayrollRunResource($payrollRun->load('company')),
            'Payroll run created successfully'
        );
    }

    /**
     * Get payrolls for a payroll run
     */
    public function getPayrolls(string $payrollRunUuid): JsonResponse
    {
        $payrolls = $this->payrollService->getPayrollsByRun($payrollRunUuid);
        return $this->successResponse(
            PayrollResource::collection($payrolls),
            'Payrolls retrieved successfully'
        );
    }

    /**
     * Generate payroll for employees
     */
    public function generatePayroll(string $payrollRunUuid): JsonResponse
    {
        try {
            $payrolls = $this->payrollService->generatePayroll($payrollRunUuid);
            return $this->successResponse(
                PayrollResource::collection($payrolls),
                'Payroll generated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], [], 422);
        }
    }

    /**
     * Get a single payroll by UUID
     */
    public function show(string $payrollUuid): JsonResponse
    {
        $payroll = $this->payrollService->findPayrollByUuid($payrollUuid);
        if (!$payroll) {
            return $this->errorResponse('Payroll not found', [], [], 404);
        }

        $payroll->load(['employee.user', 'payrollRun.company', 'earnings', 'deductions']);

        return $this->successResponse(
            new PayrollResource($payroll),
            'Payroll retrieved successfully'
        );
    }

    /**
     * Finalize a payroll (mark as paid)
     */
    public function finalize(string $payrollRunUuid): JsonResponse
    {
        try {
            $payrollRun = $this->payrollService->finalizePayroll($payrollRunUuid);
            return $this->successResponse(
                new PayrollRunResource($payrollRun->load('company')),
                'Payroll finalized successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], [], 422);
        }
    }

    /**
     * Export payroll run to Excel
     */
    public function exportExcel(string $payrollRunUuid): BinaryFileResponse|JsonResponse
    {
        try {
            $payrollRun = $this->payrollService->findPayrollRunByUuid($payrollRunUuid);
            if (!$payrollRun) {
                return $this->errorResponse('Payroll run not found', [], [], 404);
            }

            // Authorization check
            // $this->authorize('view', $payrollRun); // Uncomment when policy is ready

            $filename = 'payroll_' . $payrollRun->period_start->format('Y-m-d') . '_' . $payrollRun->period_end->format('Y-m-d') . '.xlsx';

            return Excel::download(new PayrollRunExport($payrollRun), $filename);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], [], 422);
        }
    }
}
