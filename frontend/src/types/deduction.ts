export interface Deduction {
  uuid: string
  name: string
  type: 'fixed' | 'percentage'
  created_at: string
  updated_at: string
}

export interface EmployeeDeduction {
  id: number
  employee_uuid?: string
  deduction_uuid?: string
  deduction?: Deduction
  amount: string
  created_at: string
  updated_at: string
}
