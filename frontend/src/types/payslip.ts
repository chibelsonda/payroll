import type { Payroll } from './payroll'

export interface Payslip {
  uuid: string
  payroll_uuid?: string
  payroll?: Payroll
  generated_at: string
  created_at: string
  updated_at: string
}
