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

export interface Employee {
  id: number
  uuid: string
  user_id: number
  employee_id: string
  created_at: string
  updated_at: string
  user?: User
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface RegisterData {
  first_name: string
  last_name: string
  email: string
  password: string
  password_confirmation: string
}

export interface AuthResponse {
  user: User
}
