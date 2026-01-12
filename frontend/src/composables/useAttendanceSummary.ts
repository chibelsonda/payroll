import { computed, type Ref } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { Attendance } from '@/types/attendance'

// API functions
const fetchAttendanceSummary = async (employeeUuid?: string, from?: string, to?: string): Promise<Attendance[]> => {
  const params = new URLSearchParams()
  if (employeeUuid) params.append('employee_uuid', employeeUuid)
  if (from) params.append('from', from)
  if (to) params.append('to', to)

  const response = await axios.get(`/attendance/summary?${params.toString()}`)
  return response.data.data || []
}

// Composables
export const useAttendanceSummary = (
  employeeUuid?: string | Ref<string | undefined>,
  from?: string | Ref<string | undefined>,
  to?: string | Ref<string | undefined>
) => {
  const employeeUuidValue = typeof employeeUuid === 'string' ? employeeUuid : employeeUuid?.value
  const fromValue = typeof from === 'string' ? from : from?.value
  const toValue = typeof to === 'string' ? to : to?.value

  return useQuery({
    queryKey: ['attendance-summary', employeeUuidValue, fromValue, toValue],
    queryFn: () => fetchAttendanceSummary(employeeUuidValue, fromValue, toValue),
    enabled: true,
    retry: false,
    // refetchOnMount is true by default (set in main.ts)
  })
}
