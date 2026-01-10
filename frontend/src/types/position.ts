import type { Department } from './department'

export interface Position {
  uuid: string
  department_uuid?: string
  title: string
  created_at: string
  updated_at: string
  department?: Department
}
