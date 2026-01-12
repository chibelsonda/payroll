import type { Employee } from './employee'

export interface Leave {
  uuid: string
  employee_uuid?: string
  employee?: Employee
  type: 'vacation' | 'sick'
  balance: string
  created_at: string
  updated_at: string
}

export interface LeaveRequest {
  uuid: string
  employee_uuid?: string
  employee?: Employee
  leave_type: 'vacation' | 'sick'
  start_date: string
  end_date: string
  status: 'pending' | 'approved' | 'rejected'
  created_at: string
  updated_at: string
}
