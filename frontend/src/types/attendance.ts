import type { Employee } from './employee'

export interface Attendance {
  uuid: string
  employee_uuid?: string
  employee?: Employee
  date: string
  hours_worked: string
  status: 'present' | 'absent' | 'leave' | 'incomplete' | 'needs_review'
  is_incomplete: boolean
  needs_review: boolean
  is_auto_corrected: boolean
  is_locked: boolean
  correction_reason?: string
  created_at: string
  updated_at: string
}
