<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends BaseApiController
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Get payroll summary report
     */
    public function payrollSummary(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $companyId = app('active_company_id');
        $summary = $this->reportService->getPayrollSummary(
            $companyId,
            $request->input('start_date'),
            $request->input('end_date')
        );

        return $this->successResponse(
            $summary,
            'Payroll summary retrieved successfully'
        );
    }

    /**
     * Get tax report
     */
    public function taxReport(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $companyId = app('active_company_id');
        $report = $this->reportService->getTaxReport(
            $companyId,
            $request->input('start_date'),
            $request->input('end_date')
        );

        return $this->successResponse(
            $report,
            'Tax report retrieved successfully'
        );
    }

    /**
     * Get contribution report
     */
    public function contributionReport(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $companyId = app('active_company_id');
        $report = $this->reportService->getContributionReport(
            $companyId,
            $request->input('start_date'),
            $request->input('end_date')
        );

        return $this->successResponse(
            $report,
            'Contribution report retrieved successfully'
        );
    }

    /**
     * Get employee ledger
     */
    public function employeeLedger(Request $request, string $employeeUuid): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $companyId = app('active_company_id');
        $employee = \App\Models\Employee::where('uuid', $employeeUuid)
            ->where('company_id', $companyId)
            ->first();

        if (!$employee) {
            return $this->errorResponse('Employee not found', [], [], 404);
        }

        $ledger = $this->reportService->getEmployeeLedger(
            $companyId,
            $employee->id,
            $request->input('start_date'),
            $request->input('end_date')
        );

        return $this->successResponse(
            $ledger,
            'Employee ledger retrieved successfully'
        );
    }
}
