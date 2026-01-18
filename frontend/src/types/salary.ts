import type { Employee } from './employee'

export interface Salary {
  uuid: string
  employee_uuid?: string
  employee?: Employee
  amount: string
  effective_from: string
  created_at: string
  updated_at: string
}
