export interface ActivityLog {
  id: number
  user_id?: number | null
  user?: {
    uuid: string
    first_name: string
    last_name: string
    email: string
  }
  subject_type?: string | null
  subject_id?: number | null
  action: string
  description?: string | null
  changes?: Record<string, any> | null
  ip_address?: string | null
  user_agent?: string | null
  created_at: string
  updated_at: string
}
