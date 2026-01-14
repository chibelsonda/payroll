export interface EmployeeAllowance {
  uuid?: string
  employee_uuid?: string
  employee?: {
    uuid: string
    employee_no: string
    user: {
      first_name: string
      last_name: string
    }
  }
  type: string
  description?: string | null
  amount: number
  effective_from?: string | null
  effective_to?: string | null
  created_at?: string
  updated_at?: string
}

export interface StoreEmployeeAllowanceData {
  employee_uuid: string
  type: string
  description?: string
  amount: number
  effective_from?: string
  effective_to?: string
}

export interface UpdateEmployeeAllowanceData {
  type?: string
  description?: string
  amount?: number
  effective_from?: string
  effective_to?: string
}
