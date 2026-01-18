import type { Employee } from './employee'

export interface User {
  uuid: string
  first_name: string
  last_name: string
  email: string
  avatar_url?: string
  has_company?: boolean
  role: 'owner' | 'admin' | 'hr' | 'payroll' | 'employee' | 'super-admin' | 'support'
  email_verified_at?: string
  created_at: string
  updated_at: string
  employee?: Employee
}
