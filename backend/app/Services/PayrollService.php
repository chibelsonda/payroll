<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollDeduction;
use App\Models\PayrollEarning;
use App\Models\PayrollRun;
use App\Models\Salary;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    /**
     * Get all payroll runs with pagination
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllPayrollRuns()
    {
        return PayrollRun::with('company')->orderBy('created_at', 'desc')->paginate(config('application.pagination.per_page'));
    }

    /**
     * Find a payroll run by UUID
     *
     * @param string $uuid
     * @return PayrollRun|null
     */
    public function findPayrollRunByUuid(string $uuid): ?PayrollRun
    {
        return PayrollRun::where('uuid', $uuid)->first();
    }

    /**
     * Create a new payroll run
     *
     * @param array $data
     * @return PayrollRun
     */
    public function createPayrollRun(array $data): PayrollRun
    {
        // UUID to ID conversion is already handled in StorePayrollRunRequest
        // The validated data already contains company_id
        // Always set status to 'draft' for new payroll runs
        $data['status'] = 'draft';
        return PayrollRun::create($data);
    }

    /**
     * Get payrolls for a payroll run
     *
     * @param string $payrollRunUuid
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPayrollsByRun(string $payrollRunUuid)
    {
        $payrollRun = $this->findPayrollRunByUuid($payrollRunUuid);
        if (!$payrollRun) {
            return collect();
        }

        return Payroll::with(['employee.user', 'earnings', 'deductions'])
            ->where('payroll_run_id', $payrollRun->id)
            ->get();
    }

    /**
     * Find a payroll by UUID
     *
     * @param string $uuid
     * @return Payroll|null
     */
    public function findPayrollByUuid(string $uuid): ?Payroll
    {
        return Payroll::where('uuid', $uuid)->first();
    }

    /**
     * Generate payroll for employees in a company
     *
     * @param string $payrollRunUuid
     * @return array
     */
    public function generatePayroll(string $payrollRunUuid): array
    {
        $payrollRun = $this->findPayrollRunByUuid($payrollRunUuid);
        if (!$payrollRun) {
            throw new \Exception('Payroll run not found');
        }

        if ($payrollRun->status !== 'draft') {
            throw new \Exception('Payroll run is not in draft status');
        }

        return DB::transaction(function () use ($payrollRun) {
            // Get all active employees for the company
            $employees = Employee::where('company_id', $payrollRun->company_id)
                ->where('status', 'active')
                ->with('user')
                ->get();

            $generatedPayrolls = [];

            foreach ($employees as $employee) {
                // Get the current salary for the employee
                // Find the most recent salary that is effective on or before the payroll period end date
                $currentSalary = Salary::where('employee_id', $employee->id)
                    ->where('effective_from', '<=', $payrollRun->period_end)
                    ->orderBy('effective_from', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                $basicSalary = $currentSalary ? (float) $currentSalary->amount : 0;

                // Create payroll
                $payroll = Payroll::create([
                    'payroll_run_id' => $payrollRun->id,
                    'employee_id' => $employee->id,
                    'basic_salary' => $basicSalary,
                    'gross_pay' => $basicSalary,
                    'total_deductions' => 0,
                    'net_pay' => $basicSalary,
                ]);

                // Calculate totals
                $this->calculatePayrollTotals($payroll);

                // Load relationships for the resource
                $payroll->load(['employee.user', 'earnings', 'deductions']);

                $generatedPayrolls[] = $payroll;
            }

            // Update payroll run status to processed
            $payrollRun->update(['status' => 'processed']);

            return $generatedPayrolls;
        });
    }

    /**
     * Calculate payroll totals (gross_pay, total_deductions, net_pay)
     *
     * @param Payroll $payroll
     * @return Payroll
     */
    public function calculatePayrollTotals(Payroll $payroll): Payroll
    {
        // Sum all earnings
        $totalEarnings = PayrollEarning::where('payroll_id', $payroll->id)->sum('amount');

        // Calculate gross pay = basic_salary + total_earnings
        $grossPay = $payroll->basic_salary + $totalEarnings;

        // Sum all deductions
        $totalDeductions = PayrollDeduction::where('payroll_id', $payroll->id)->sum('amount');

        // Calculate net pay = gross_pay - total_deductions
        $netPay = $grossPay - $totalDeductions;

        // Update payroll
        $payroll->update([
            'gross_pay' => $grossPay,
            'total_deductions' => $totalDeductions,
            'net_pay' => $netPay,
        ]);

        return $payroll->fresh();
    }

    /**
     * Finalize a payroll (mark payroll run as paid)
     *
     * @param string $payrollRunUuid
     * @return PayrollRun
     */
    public function finalizePayroll(string $payrollRunUuid): PayrollRun
    {
        $payrollRun = $this->findPayrollRunByUuid($payrollRunUuid);
        if (!$payrollRun) {
            throw new \Exception('Payroll run not found');
        }

        if ($payrollRun->status !== 'processed') {
            throw new \Exception('Payroll run must be processed before finalization');
        }

        $payrollRun->update(['status' => 'paid']);

        return $payrollRun->fresh();
    }
}
