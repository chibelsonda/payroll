import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import type { Ref } from 'vue'
import { unref } from 'vue'
import axios from '@/lib/axios'

export interface AttendanceSettings {
  id?: number
  company_id?: number
  company_uuid?: string
  default_shift_start: string
  default_break_start: string
  default_break_end: string
  default_shift_end: string
  max_shift_hours: number
  auto_close_missing_out: boolean
  auto_deduct_break: boolean
  enable_auto_correction: boolean
  is_default?: boolean
  created_at?: string
  updated_at?: string
}

export interface UpdateAttendanceSettingsData {
  company_uuid: string
  default_shift_start: string
  default_break_start: string
  default_break_end: string
  default_shift_end: string
  max_shift_hours: number
  auto_close_missing_out: boolean
  auto_deduct_break: boolean
  enable_auto_correction: boolean
}

// API functions
const fetchAttendanceSettings = async (companyUuid?: string | Ref<string | undefined>): Promise<AttendanceSettings> => {
  // Unwrap ref if it's a ref, otherwise use the value directly
  const uuidValue = unref(companyUuid)
  
  const params = new URLSearchParams()
  if (uuidValue && typeof uuidValue === 'string') {
    params.append('company_uuid', uuidValue)
  }

  const url = params.toString() ? `/attendance-settings?${params.toString()}` : '/attendance-settings'
  const response = await axios.get(url)
  return response.data.data
}

const updateAttendanceSettings = async (data: UpdateAttendanceSettingsData): Promise<AttendanceSettings> => {
  const response = await axios.put('/attendance-settings', data)
  return response.data.data
}

// Composables
export const useAttendanceSettings = (companyUuid?: string | Ref<string | undefined>) => {
  // Unwrap the ref to get the actual value for the query key
  const uuidValue = typeof companyUuid === 'string' ? companyUuid : companyUuid?.value
  
  return useQuery({
    queryKey: ['attendance-settings', uuidValue],
    queryFn: () => fetchAttendanceSettings(companyUuid),
    enabled: true,
    retry: false,
    refetchOnMount: true,
  })
}

export const useUpdateAttendanceSettings = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: updateAttendanceSettings,
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['attendance-settings'] })
      queryClient.invalidateQueries({ queryKey: ['attendances'] })
      queryClient.invalidateQueries({ queryKey: ['attendance-summary'] })
    },
  })
}
