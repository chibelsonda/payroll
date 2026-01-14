<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceSettings;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeAllowance;
use App\Models\EmployeeContribution;
use App\Models\EmployeeDeduction;
use App\Models\Payroll;
use App\Models\PayrollDeduction;
use App\Models\PayrollEarning;
use App\Models\PayrollRun;
use App\Models\Salary;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function __construct(
        protected TaxCalculationService $taxCalculationService,
        protected ContributionCalculationService $contributionCalculationService
    ) {}

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

        if ($payrollRun->is_locked) {
            throw new \Exception('Payroll run is locked and cannot be regenerated');
        }

        return DB::transaction(function () use ($payrollRun) {
            // Get all active employees for the company
            $employees = Employee::where('company_id', $payrollRun->company_id)
                ->where('status', 'active')
                ->with(['user', 'employeeAllowances', 'employeeDeductions.deduction'])
                ->get();

            $generatedPayrolls = [];

            // Get attendance settings for the company
            $attendanceSettings = $this->getAttendanceSettings($payrollRun->company_id);
            $regularHoursPerDay = $attendanceSettings['max_shift_hours'] ?? 8;

            foreach ($employees as $employee) {
                // Get the current salary for the employee
                // Find the most recent salary that is effective on or before the payroll period end date
                $currentSalary = Salary::where('employee_id', $employee->id)
                    ->where('effective_from', '<=', $payrollRun->period_end)
                    ->orderBy('effective_from', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->first();

                $monthlySalary = $currentSalary ? (float) $currentSalary->amount : 0;

                // Calculate attendance-based payroll
                $attendanceData = $this->calculateAttendanceBasedPayroll(
                    $employee,
                    $payrollRun->period_start,
                    $payrollRun->period_end,
                    $monthlySalary,
                    $regularHoursPerDay
                );

                // Create payroll with attendance-based calculations
                $payroll = Payroll::create([
                    'payroll_run_id' => $payrollRun->id,
                    'employee_id' => $employee->id,
                    'basic_salary' => $attendanceData['basic_salary'],
                    'gross_pay' => $attendanceData['basic_salary'],
                    'total_deductions' => 0,
                    'net_pay' => $attendanceData['basic_salary'],
                ]);

                // Add overtime earnings if applicable
                if ($attendanceData['overtime_hours'] > 0 && $attendanceData['overtime_pay'] > 0) {
                    PayrollEarning::create([
                        'payroll_id' => $payroll->id,
                        'type' => 'overtime',
                        'description' => sprintf(
                            'Overtime (%s hours)',
                            number_format($attendanceData['overtime_hours'], 2)
                        ),
                        'amount' => $attendanceData['overtime_pay'],
                    ]);
                }

                // Add employee allowances
                $this->applyEmployeeAllowances($payroll, $employee, $payrollRun->period_start, $payrollRun->period_end);

                // Calculate gross pay first (basic_salary + earnings)
                $this->calculatePayrollTotals($payroll);
                $payroll->refresh();
                $grossPay = $payroll->gross_pay;

                // Apply deductions and contributions
                $this->applyDeductionsAndContributions($payroll, $employee, $grossPay, $monthlySalary);

                // Recalculate totals with all deductions applied
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
     * Calculate attendance-based payroll for an employee
     *
     * @param Employee $employee
     * @param \DateTime|string $periodStart
     * @param \DateTime|string $periodEnd
     * @param float $monthlySalary
     * @param int $regularHoursPerDay
     * @return array
     */
    protected function calculateAttendanceBasedPayroll(
        Employee $employee,
        $periodStart,
        $periodEnd,
        float $monthlySalary,
        int $regularHoursPerDay
    ): array {
        // Convert dates to Carbon instances
        $startDate = Carbon::parse($periodStart);
        $endDate = Carbon::parse($periodEnd);

        // Get attendance records for the payroll period
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->where('status', '!=', 'absent') // Exclude absent days
            ->get();

        // Calculate total hours worked from attendance records
        $totalHoursWorked = (float) $attendances->sum('hours_worked');

        // Calculate working days in the period (excluding weekends - adjust if needed)
        $totalWorkingDays = $this->calculateWorkingDays($startDate, $endDate);
        $expectedRegularHours = $totalWorkingDays * $regularHoursPerDay;

        // Calculate actual working days (days with attendance)
        $actualWorkingDays = $attendances->count();

        // Calculate regular hours and overtime
        $regularHours = min($totalHoursWorked, $expectedRegularHours);
        $overtimeHours = max(0, $totalHoursWorked - $expectedRegularHours);

        // Calculate hourly rate (assuming monthly salary is for full month)
        // For payroll period, we need to prorate based on days worked
        $hourlyRate = $expectedRegularHours > 0
            ? ($monthlySalary / ($totalWorkingDays * $regularHoursPerDay))
            : 0;

        // Calculate basic salary (prorated based on attendance)
        // Option 1: Based on days worked
        $attendanceRatio = $totalWorkingDays > 0
            ? ($actualWorkingDays / $totalWorkingDays)
            : 0;

        // Option 2: Based on hours worked (alternative approach)
        // $attendanceRatio = $expectedRegularHours > 0
        //     ? ($regularHours / $expectedRegularHours)
        //     : 0;

        $basicSalary = $monthlySalary * $attendanceRatio;

        // Calculate overtime pay (1.5x hourly rate for overtime hours)
        $overtimeRate = $hourlyRate * 1.5;
        $overtimePay = $overtimeHours * $overtimeRate;

        return [
            'total_hours_worked' => $totalHoursWorked,
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'expected_regular_hours' => $expectedRegularHours,
            'actual_working_days' => $actualWorkingDays,
            'total_working_days' => $totalWorkingDays,
            'basic_salary' => round($basicSalary, 2),
            'overtime_pay' => round($overtimePay, 2),
            'hourly_rate' => round($hourlyRate, 2),
            'overtime_rate' => round($overtimeRate, 2),
        ];
    }

    /**
     * Calculate working days in a period (excluding weekends)
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int
     */
    protected function calculateWorkingDays(Carbon $startDate, Carbon $endDate): int
    {
        $workingDays = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            // Count weekdays (Monday = 1, Friday = 5)
            if ($currentDate->dayOfWeek >= Carbon::MONDAY && $currentDate->dayOfWeek <= Carbon::FRIDAY) {
                $workingDays++;
            }
            $currentDate->addDay();
        }

        return $workingDays;
    }

    /**
     * Apply employee allowances to payroll
     *
     * @param Payroll $payroll
     * @param Employee $employee
     * @param \DateTime|string $periodStart
     * @param \DateTime|string $periodEnd
     * @return void
     */
    protected function applyEmployeeAllowances(Payroll $payroll, Employee $employee, $periodStart, $periodEnd): void
    {
        $startDate = Carbon::parse($periodStart);
        $endDate = Carbon::parse($periodEnd);

        // Get active allowances for the payroll period
        $allowances = EmployeeAllowance::where('employee_id', $employee->id)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $endDate);
            })
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $startDate);
            })
            ->get();

        foreach ($allowances as $allowance) {
            PayrollEarning::create([
                'payroll_id' => $payroll->id,
                'type' => 'allowance',
                'description' => $allowance->description ?? ucfirst($allowance->type) . ' Allowance',
                'amount' => $allowance->amount,
            ]);
        }
    }

    /**
     * Apply deductions and contributions to payroll
     *
     * @param Payroll $payroll
     * @param Employee $employee
     * @param float $grossPay
     * @param float $monthlySalary
     * @return void
     */
    protected function applyDeductionsAndContributions(Payroll $payroll, Employee $employee, float $grossPay, float $monthlySalary): void
    {
        // 1. Apply employee deductions
        $employeeDeductions = EmployeeDeduction::where('employee_id', $employee->id)
            ->with('deduction')
            ->get();

        foreach ($employeeDeductions as $employeeDeduction) {
            PayrollDeduction::create([
                'payroll_id' => $payroll->id,
                'type' => $employeeDeduction->deduction->type ?? 'other',
                'description' => $employeeDeduction->deduction->name ?? 'Deduction',
                'amount' => $employeeDeduction->amount,
            ]);
        }

        // 2. Calculate and apply government contributions (SSS, PhilHealth, Pag-IBIG)
        $contributions = $this->contributionCalculationService->calculateAllContributions($monthlySalary);

        // SSS
        if ($contributions['sss']['employee'] > 0) {
            PayrollDeduction::create([
                'payroll_id' => $payroll->id,
                'type' => 'sss',
                'description' => 'SSS Contribution',
                'amount' => $contributions['sss']['employee'],
            ]);
        }

        // PhilHealth
        if ($contributions['philhealth']['employee'] > 0) {
            PayrollDeduction::create([
                'payroll_id' => $payroll->id,
                'type' => 'philhealth',
                'description' => 'PhilHealth Contribution',
                'amount' => $contributions['philhealth']['employee'],
            ]);
        }

        // Pag-IBIG
        if ($contributions['pagibig']['employee'] > 0) {
            PayrollDeduction::create([
                'payroll_id' => $payroll->id,
                'type' => 'pagibig',
                'description' => 'Pag-IBIG Contribution',
                'amount' => $contributions['pagibig']['employee'],
            ]);
        }

        // 3. Calculate and apply tax (withholding tax)
        $taxAmount = $this->taxCalculationService->calculateWithholdingTax($grossPay);
        if ($taxAmount > 0) {
            PayrollDeduction::create([
                'payroll_id' => $payroll->id,
                'type' => 'tax',
                'description' => 'Withholding Tax',
                'amount' => $taxAmount,
            ]);
        }
    }

    /**
     * Get attendance settings for a company
     *
     * @param int $companyId
     * @return array
     */
    protected function getAttendanceSettings(int $companyId): array
    {
        $settings = AttendanceSettings::where('company_id', $companyId)->first();

        if ($settings) {
            return [
                'max_shift_hours' => $settings->max_shift_hours ?? 8,
                'default_shift_start' => $settings->default_shift_start,
                'default_shift_end' => $settings->default_shift_end,
            ];
        }

        return AttendanceSettings::getDefaults();
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

        // Lock the payroll run and mark as paid
        $payrollRun->update([
            'status' => 'paid',
            'is_locked' => true,
        ]);

        return $payrollRun->fresh();
    }
}
