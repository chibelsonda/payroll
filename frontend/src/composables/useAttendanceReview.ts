import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { Attendance } from '@/types/attendance'

// API functions
const fetchAttendanceNeedingReview = async (): Promise<Attendance[]> => {
  const response = await axios.get('/attendance/summary?needs_review=true')
  return response.data.data || []
}

const resolveAttendance = async (attendanceUuid: string): Promise<Attendance> => {
  const response = await axios.post(`/attendance/${attendanceUuid}/resolve`)
  return response.data.data
}

const fixAttendance = async (attendanceUuid: string): Promise<Attendance> => {
  const response = await axios.post(`/attendance/${attendanceUuid}/fix`)
  return response.data.data
}

// Composables
export const useAttendanceNeedingReview = () => {
  return useQuery({
    queryKey: ['attendance-needing-review'],
    queryFn: fetchAttendanceNeedingReview,
    enabled: true,
    retry: false,
    refetchOnMount: true,
    refetchOnWindowFocus: false,
  })
}

export const useResolveAttendance = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: resolveAttendance,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['attendance-needing-review'] })
      queryClient.invalidateQueries({ queryKey: ['attendances'] })
      queryClient.invalidateQueries({ queryKey: ['attendance-summary'] })
    },
  })
}

export const useFixAttendance = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: fixAttendance,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['attendance-needing-review'] })
      queryClient.invalidateQueries({ queryKey: ['attendances'] })
      queryClient.invalidateQueries({ queryKey: ['attendance-summary'] })
      queryClient.invalidateQueries({ queryKey: ['attendance-logs'] })
    },
  })
}
