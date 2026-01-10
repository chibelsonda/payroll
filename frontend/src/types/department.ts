import type { Company } from './company'

export interface Department {
  uuid: string
  company_uuid?: string
  name: string
  created_at: string
  updated_at: string
  company?: Company
}
