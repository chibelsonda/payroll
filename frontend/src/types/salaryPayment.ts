import type { Payroll } from './payroll'

export interface SalaryPayment {
  uuid: string
  payroll_uuid?: string
  payroll?: Payroll
  method: 'bank' | 'cash'
  reference_no?: string
  paid_at?: string
  created_at: string
  updated_at: string
}
