<?php

namespace App\Exports;

use App\Models\PayrollRun;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class PayrollRunExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithColumnFormatting
{
    protected $payrollRun;

    public function __construct(PayrollRun $payrollRun)
    {
        $this->payrollRun = $payrollRun->load([
            'payrolls.employee.user',
            'payrolls.employee.company',
            'payrolls.employee.department',
            'payrolls.employee.position',
            'payrolls.earnings',
            'payrolls.deductions',
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->payrollRun->payrolls;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Employee No',
            'Employee Name',
            'Company',
            'Department',
            'Position',
            'Basic Salary',
            'Allowances Total',
            'Overtime Total',
            'Bonus Total',
            'Gross Pay',
            'Tax',
            'SSS',
            'PhilHealth',
            'Pag-IBIG',
            'Total Deductions',
            'Net Pay',
        ];
    }

    /**
     * @param mixed $payroll
     * @return array
     */
    public function map($payroll): array
    {
        // Calculate earnings totals
        $allowancesTotal = $payroll->earnings->where('type', 'allowance')->sum('amount');
        $overtimeTotal = $payroll->earnings->where('type', 'overtime')->sum('amount');
        $bonusTotal = $payroll->earnings->where('type', 'bonus')->sum('amount');

        // Calculate deductions by type
        $tax = $payroll->deductions->where('type', 'tax')->sum('amount');
        $sss = $payroll->deductions->where('type', 'sss')->sum('amount');
        $philhealth = $payroll->deductions->where('type', 'philhealth')->sum('amount');
        $pagibig = $payroll->deductions->where('type', 'pagibig')->sum('amount');

        return [
            $payroll->employee->employee_no ?? '',
            ($payroll->employee->user->first_name ?? '') . ' ' . ($payroll->employee->user->last_name ?? ''),
            $payroll->employee->company->name ?? '',
            $payroll->employee->department->name ?? '',
            $payroll->employee->position->title ?? '',
            (float) $payroll->basic_salary,
            (float) $allowancesTotal,
            (float) $overtimeTotal,
            (float) $bonusTotal,
            (float) $payroll->gross_pay,
            (float) $tax,
            (float) $sss,
            (float) $philhealth,
            (float) $pagibig,
            (float) $payroll->total_deductions,
            (float) $payroll->net_pay,
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FFE0E0E0',
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15,  // Employee No
            'B' => 25,  // Employee Name
            'C' => 20,  // Company
            'D' => 20,  // Department
            'E' => 20,  // Position
            'F' => 15,  // Basic Salary
            'G' => 18,  // Allowances Total
            'H' => 18,  // Overtime Total
            'I' => 15,  // Bonus Total
            'J' => 15,  // Gross Pay
            'K' => 12,  // Tax
            'L' => 12,  // SSS
            'M' => 15,  // PhilHealth
            'N' => 15,  // Pag-IBIG
            'O' => 18,  // Total Deductions
            'P' => 15,  // Net Pay
        ];
    }

    /**
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,  // Basic Salary
            'G' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,  // Allowances Total
            'H' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,  // Overtime Total
            'I' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,  // Bonus Total
            'J' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,  // Gross Pay
            'K' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,  // Tax
            'L' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,  // SSS
            'M' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,  // PhilHealth
            'N' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,  // Pag-IBIG
            'O' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,  // Total Deductions
            'P' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,  // Net Pay
        ];
    }
}
