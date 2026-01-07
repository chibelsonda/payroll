export interface User {
  id: string
  uuid: string
  first_name: string
  last_name: string
  email: string
  role: 'admin' | 'student'
  email_verified_at?: string
  created_at: string
  updated_at: string
  student?: Student
}

export interface Student {
  id: number
  uuid: string
  user_id: number
  student_id: string
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
  token: string
}