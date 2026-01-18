import type { Employee } from './employee'

export interface Contribution {
  uuid: string
  name: string
  employee_share: string
  employer_share: string
  created_at: string
  updated_at: string
}

export interface EmployeeContribution {
  id: number
  employee_uuid?: string
  employee?: Employee
  contribution_uuid?: string
  contribution?: Contribution
  created_at: string
  updated_at: string
}
