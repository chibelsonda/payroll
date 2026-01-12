import type { Employee } from './employee'

export interface Attendance {
  uuid: string
  employee_uuid?: string
  employee?: Employee
  date: string
  time_in?: string
  time_out?: string
  hours_worked: string
  created_at: string
  updated_at: string
}
