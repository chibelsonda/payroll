import type { Employee } from './employee'
import type { Payroll } from './payroll'

export interface Loan {
  uuid: string
  employee_uuid?: string
  employee?: Employee
  amount: string
  balance: string
  start_date: string
  created_at: string
  updated_at: string
}

export interface LoanPayment {
  id: number
  loan_uuid?: string
  loan?: Loan
  payroll_uuid?: string
  payroll?: Payroll
  amount: string
  created_at: string
  updated_at: string
}
