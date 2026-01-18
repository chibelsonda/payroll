export interface PayrollSummaryReport {
  total_payroll_runs: number
  total_employees_paid: number
  total_gross_pay: number
  total_net_pay: number
  total_deductions: number
  total_allowances: number
  total_overtime: number
  total_tax: number
  total_sss_contribution: number
  total_philhealth_contribution: number
  total_pagibig_contribution: number
}

export interface TaxReportEntry {
  payroll_run_uuid: string
  payroll_run_period: string
  employee_name: string
  employee_no: string
  tax_amount: number
  gross_pay: number
  net_pay: number
}

export interface ContributionReportEntry {
  payroll_run_uuid: string
  payroll_run_period: string
  employee_name: string
  employee_no: string
  sss_contribution: number
  philhealth_contribution: number
  pagibig_contribution: number
  total_contributions: number
}

export interface EmployeeLedgerEntry {
  payroll_run_uuid: string
  period_start: string
  period_end: string
  basic_salary: number
  gross_pay: number
  total_deductions: number
  net_pay: number
  earnings: Array<{
    type: string
    description: string
    amount: number
  }>
  deductions: Array<{
    type: string
    description: string
    amount: number
  }>
}
