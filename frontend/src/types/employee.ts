import type { User } from './user'
import type { Company } from './company'
import type { Department } from './department'
import type { Position } from './position'

export interface Employee {
  uuid: string
  employee_no: string
  company_uuid?: string
  department_uuid?: string
  position_uuid?: string
  employment_type?: 'regular' | 'contractual' | 'probationary'
  hire_date?: string
  termination_date?: string
  status?: 'active' | 'inactive' | 'terminated'
  created_at: string
  updated_at: string
  user?: User
  company?: Company
  department?: Department
  position?: Position
}
