<?php

namespace App\Exports;

use App\Models\PayrollRun;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class PayrollRunExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithColumnFormatting, WithEvents
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
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Style header row
        $headerStyle = [
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1976D2'], // Primary blue color
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];

        // Apply header style to all header cells
        $headerRange = 'A1:' . $highestColumn . '1';
        $sheet->getStyle($headerRange)->applyFromArray($headerStyle);

        // Set header row height
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Style data rows with borders
        $dataRange = 'A2:' . $highestColumn . $highestRow;
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCCCCCC'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Set column alignments
        $sheet->getStyle('A2:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B2:E' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('F2:P' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Add alternating row colors
        for ($row = 2; $row <= $highestRow; $row++) {
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':' . $highestColumn . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFF5F5F5'],
                    ],
                ]);
            }
        }

        // Freeze header row
        $sheet->freezePane('A2');

        return [];
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
        // Use number format with 2 decimal places (PHP format)
        $numberFormat = '#,##0.00';
        return [
            'F' => $numberFormat,  // Basic Salary
            'G' => $numberFormat,  // Allowances Total
            'H' => $numberFormat,  // Overtime Total
            'I' => $numberFormat,  // Bonus Total
            'J' => $numberFormat,  // Gross Pay
            'K' => $numberFormat,  // Tax
            'L' => $numberFormat,  // SSS
            'M' => $numberFormat,  // PhilHealth
            'N' => $numberFormat,  // Pag-IBIG
            'O' => $numberFormat,  // Total Deductions
            'P' => $numberFormat,  // Net Pay
        ];
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Add totals row
                $totalsRow = $highestRow + 2;

                // Set "Totals" label
                $sheet->setCellValue('E' . $totalsRow, 'TOTALS:');
                $sheet->getStyle('E' . $totalsRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    ],
                ]);

                // Calculate and set totals for currency columns
                $currencyColumns = ['F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
                foreach ($currencyColumns as $column) {
                    $cellAddress = $column . $totalsRow;
                    $formula = '=SUM(' . $column . '2:' . $column . $highestRow . ')';
                    $sheet->setCellValue($cellAddress, $formula);
                    $sheet->getStyle($cellAddress)->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 11,
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        ],
                        'borders' => [
                            'top' => [
                                'borderStyle' => Border::BORDER_DOUBLE,
                                'color' => ['argb' => 'FF000000'],
                            ],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFE3F2FD'],
                        ],
                    ]);
                    // Set number format separately
                    $sheet->getStyle($cellAddress)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                // Style the totals label cell
                $sheet->getStyle('E' . $totalsRow)->applyFromArray([
                    'borders' => [
                        'top' => [
                            'borderStyle' => Border::BORDER_DOUBLE,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFE3F2FD'],
                    ],
                ]);
            },
        ];
    }
}
