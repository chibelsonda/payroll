import type { Student } from './auth'

export interface Enrollment {
  id: number
  uuid: string
  student_id: number
  subject_id: number
  status: 'active' | 'inactive'
  created_at: string
  updated_at: string
  student: Student
  subject: {
    id: number
    uuid: string
    code: string
    name: string
    credits: number
  }
}
