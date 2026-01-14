export interface Holiday {
  uuid: string
  name: string
  date: string
  type: 'regular' | 'special' | 'local'
  is_recurring: boolean
  description?: string | null
  company?: {
    uuid: string
    name: string
  }
  created_at?: string
  updated_at?: string
}

export interface StoreHolidayData {
  name: string
  date: string
  type: 'regular' | 'special' | 'local'
  is_recurring?: boolean
  description?: string
}

export interface UpdateHolidayData {
  name?: string
  date?: string
  type?: 'regular' | 'special' | 'local'
  is_recurring?: boolean
  description?: string
}
