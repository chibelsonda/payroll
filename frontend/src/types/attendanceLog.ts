import type { Employee } from './employee'

export interface AttendanceLog {
  uuid: string
  employee_uuid?: string
  employee?: Employee
  log_time: string
  type: 'IN' | 'OUT'
  is_auto_corrected: boolean
  correction_reason?: string | null
  created_at: string
  updated_at: string
}
