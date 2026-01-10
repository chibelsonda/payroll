import type { Employee } from './employee'

export interface User {
  uuid: string
  first_name: string
  last_name: string
  email: string
  role: 'admin' | 'employee'
  email_verified_at?: string
  created_at: string
  updated_at: string
  employee?: Employee
}
