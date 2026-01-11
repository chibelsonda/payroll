import type { Company } from './company'
import type { Employee } from './employee'

export interface PayrollRun {
  uuid: string
  company_uuid?: string
  company?: Company
  period_start: string
  period_end: string
  pay_date: string
  status: 'draft' | 'processed' | 'paid'
  created_at: string
  updated_at: string
}

export interface PayrollEarning {
  uuid: string
  type: string
  description?: string
  amount: string
  created_at: string
  updated_at: string
}

export interface PayrollDeduction {
  uuid: string
  type: string
  description?: string
  amount: string
  created_at: string
  updated_at: string
}

export interface Payroll {
  uuid: string
  payroll_run_uuid?: string
  payroll_run?: PayrollRun
  employee_uuid?: string
  employee?: Employee
  basic_salary: string
  gross_pay: string
  total_deductions: string
  net_pay: string
  earnings?: PayrollEarning[]
  deductions?: PayrollDeduction[]
  created_at: string
  updated_at: string
}
