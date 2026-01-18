import { type Ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { AttendanceLog } from '@/types/attendanceLog'

type ImportResult = {
  created: number
  skipped: number
  failed: number
  errors: { row: number; message: string }[]
}

// API functions
const fetchAttendanceLogs = async (employeeUuid?: string, date?: string): Promise<AttendanceLog[]> => {
  const params = new URLSearchParams()
  if (employeeUuid) params.append('employee_uuid', employeeUuid)
  if (date) params.append('date', date)

  const response = await axios.get(`/attendance/logs?${params.toString()}`)
  return response.data.data || []
}

const createAttendanceLog = async (data: {
  employee_uuid: string
  type: 'IN' | 'OUT'
  log_time?: string
}): Promise<AttendanceLog> => {
  const response = await axios.post('/attendance/logs', data)
  return response.data.data
}

const deleteAttendanceLog = async (uuid: string): Promise<void> => {
  await axios.delete(`/attendance/logs/${uuid}`)
}

const importAttendanceLogs = async (file: File): Promise<ImportResult> => {
  const formData = new FormData()
  formData.append('file', file)

  const response = await axios.post('/attendance/logs/import', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })

  return response.data.data
}

// Composables
export const useAttendanceLogs = (employeeUuid?: string | Ref<string | undefined>, date?: string | Ref<string | undefined>) => {
  const employeeUuidValue = typeof employeeUuid === 'string' ? employeeUuid : employeeUuid?.value
  const dateValue = typeof date === 'string' ? date : date?.value

  return useQuery({
    queryKey: ['attendance-logs', employeeUuidValue, dateValue],
    queryFn: () => fetchAttendanceLogs(employeeUuidValue, dateValue),
    enabled: !!employeeUuidValue || !!dateValue, // Enable if we have at least employee or date filter
    retry: false,
    // refetchOnMount is true by default (set in main.ts)
  })
}

export const useCreateAttendanceLog = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: createAttendanceLog,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['attendance-logs'] })
      queryClient.invalidateQueries({ queryKey: ['attendances'] })
      queryClient.invalidateQueries({ queryKey: ['attendance-summary'] })
    },
  })
}

export const useDeleteAttendanceLog = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: deleteAttendanceLog,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['attendance-logs'] })
      queryClient.invalidateQueries({ queryKey: ['attendances'] })
      queryClient.invalidateQueries({ queryKey: ['attendance-summary'] })
    },
  })
}

export const useImportAttendanceLogs = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: importAttendanceLogs,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['attendance-logs'] })
      queryClient.invalidateQueries({ queryKey: ['attendances'] })
      queryClient.invalidateQueries({ queryKey: ['attendance-summary'] })
    },
  })
}
