export interface Shift {
  uuid: string
  name: string
  start_time: string
  end_time: string
  break_duration_minutes: number
  is_active: boolean
  description?: string | null
  company?: {
    uuid: string
    name: string
  }
  created_at?: string
  updated_at?: string
}

export interface StoreShiftData {
  name: string
  start_time: string
  end_time: string
  break_duration_minutes?: number
  is_active?: boolean
  description?: string
}

export interface UpdateShiftData {
  name?: string
  start_time?: string
  end_time?: string
  break_duration_minutes?: number
  is_active?: boolean
  description?: string
}
