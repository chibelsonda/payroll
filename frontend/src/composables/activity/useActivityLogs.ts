import { useQuery } from '@tanstack/vue-query'
import { apiAxios } from '@/lib/axios'
import type { ActivityLog } from '@/types/activityLog'

export function useActivityLogs(filters?: { user_id?: number; action?: string; subject_type?: string }) {
  return useQuery({
    queryKey: ['activity-logs', filters],
    queryFn: async () => {
      const response = await apiAxios.get<{ data: ActivityLog[] }>('/activity-logs', { params: filters })
      return response.data.data
    },
  })
}
