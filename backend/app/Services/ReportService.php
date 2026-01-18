<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\PayrollRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Get payroll summary for a date range
     */
    public function getPayrollSummary(int $companyId, string $startDate, string $endDate): array
    {
        $payrollRuns = PayrollRun::where('company_id', $companyId)
            ->whereBetween('period_start', [$startDate, $endDate])
            ->where('status', 'processed')
            ->with(['payrolls.earnings', 'payrolls.deductions'])
            ->get();

        $totalGrossPay = 0;
        $totalDeductions = 0;
        $totalNetPay = 0;
        $employeeCount = 0;

        foreach ($payrollRuns as $run) {
            foreach ($run->payrolls as $payroll) {
                $totalGrossPay += $payroll->gross_pay;
                $totalDeductions += $payroll->total_deductions;
                $totalNetPay += $payroll->net_pay;
                $employeeCount++;
            }
        }

        return [
            'period_start' => $startDate,
            'period_end' => $endDate,
            'payroll_runs_count' => $payrollRuns->count(),
            'employee_count' => $employeeCount,
            'total_gross_pay' => round($totalGrossPay, 2),
            'total_deductions' => round($totalDeductions, 2),
            'total_net_pay' => round($totalNetPay, 2),
        ];
    }

    /**
     * Get tax report for a date range
     */
    public function getTaxReport(int $companyId, string $startDate, string $endDate): array
    {
        $payrollRuns = PayrollRun::where('company_id', $companyId)
            ->whereBetween('period_start', [$startDate, $endDate])
            ->where('status', 'processed')
            ->with(['payrolls.deductions'])
            ->get();

        $totalTax = 0;
        $taxBreakdown = [];

        foreach ($payrollRuns as $run) {
            foreach ($run->payrolls as $payroll) {
                $taxAmount = $payroll->deductions->where('type', 'tax')->sum('amount');
                $totalTax += $taxAmount;

                if (!isset($taxBreakdown[$run->period_start->format('Y-m')])) {
                    $taxBreakdown[$run->period_start->format('Y-m')] = 0;
                }
                $taxBreakdown[$run->period_start->format('Y-m')] += $taxAmount;
            }
        }

        return [
            'period_start' => $startDate,
            'period_end' => $endDate,
            'total_tax' => round($totalTax, 2),
            'tax_breakdown' => $taxBreakdown,
        ];
    }

    /**
     * Get contribution report for a date range
     */
    public function getContributionReport(int $companyId, string $startDate, string $endDate): array
    {
        $payrollRuns = PayrollRun::where('company_id', $companyId)
            ->whereBetween('period_start', [$startDate, $endDate])
            ->where('status', 'processed')
            ->with(['payrolls.deductions'])
            ->get();

        $totalSss = 0;
        $totalPhilhealth = 0;
        $totalPagibig = 0;

        foreach ($payrollRuns as $run) {
            foreach ($run->payrolls as $payroll) {
                $totalSss += $payroll->deductions->where('type', 'sss')->sum('amount');
                $totalPhilhealth += $payroll->deductions->where('type', 'philhealth')->sum('amount');
                $totalPagibig += $payroll->deductions->where('type', 'pagibig')->sum('amount');
            }
        }

        return [
            'period_start' => $startDate,
            'period_end' => $endDate,
            'sss' => round($totalSss, 2),
            'philhealth' => round($totalPhilhealth, 2),
            'pagibig' => round($totalPagibig, 2),
            'total' => round($totalSss + $totalPhilhealth + $totalPagibig, 2),
        ];
    }

    /**
     * Get employee ledger for a specific employee
     */
    public function getEmployeeLedger(int $companyId, int $employeeId, string $startDate, string $endDate): array
    {
        $payrolls = Payroll::whereHas('payrollRun', function ($query) use ($companyId, $startDate, $endDate) {
            $query->where('company_id', $companyId)
                ->whereBetween('period_start', [$startDate, $endDate])
                ->where('status', 'processed');
        })
            ->where('employee_id', $employeeId)
            ->with(['payrollRun', 'earnings', 'deductions'])
            ->orderBy('created_at', 'desc')
            ->get();

        $ledger = [];
        foreach ($payrolls as $payroll) {
            $ledger[] = [
                'payroll_run_uuid' => $payroll->payrollRun->uuid,
                'period_start' => $payroll->payrollRun->period_start->format('Y-m-d'),
                'period_end' => $payroll->payrollRun->period_end->format('Y-m-d'),
                'basic_salary' => $payroll->basic_salary,
                'gross_pay' => $payroll->gross_pay,
                'total_deductions' => $payroll->total_deductions,
                'net_pay' => $payroll->net_pay,
                'earnings' => $payroll->earnings->map(function ($earning) {
                    return [
                        'type' => $earning->type,
                        'description' => $earning->description,
                        'amount' => $earning->amount,
                    ];
                }),
                'deductions' => $payroll->deductions->map(function ($deduction) {
                    return [
                        'type' => $deduction->type,
                        'description' => $deduction->description,
                        'amount' => $deduction->amount,
                    ];
                }),
            ];
        }

        return $ledger;
    }
}
