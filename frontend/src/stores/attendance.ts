import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useAttendanceLogs, useCreateAttendanceLog } from '@/composables/useAttendanceLogs'
import { useAttendanceSummary } from '@/composables/useAttendanceSummary'
import { useAuthStore } from './auth'
import type { AttendanceLog } from '@/types/attendanceLog'
import type { Attendance } from '@/types/attendance'

export const useAttendanceStore = defineStore('attendance', () => {
  const authStore = useAuthStore()
  const employeeUuid = computed(() => authStore.user?.employee?.uuid)
  const today = computed(() => new Date().toISOString().split('T')[0])

  // State
  const loading = ref(false)

  // Composables for today's data
  const { data: logsData, isLoading: isLoadingLogs, refetch: refetchLogs } = useAttendanceLogs(
    employeeUuid,
    today
  )
  const { data: summaryData, isLoading: isLoadingSummary, refetch: refetchSummary } = useAttendanceSummary(
    employeeUuid,
    today,
    today
  )
  const createMutation = useCreateAttendanceLog()

  // Computed state
  const logs = computed(() => logsData.value || [])
  const summary = computed(() => {
    const summaries = summaryData.value || []
    return summaries.find(s => s.date === today.value) || null
  })

  // Get today's logs only, sorted chronologically
  const todayLogs = computed(() => {
    return logs.value
      .filter(log => log.log_time.startsWith(today.value))
      .sort((a, b) => new Date(a.log_time).getTime() - new Date(b.log_time).getTime())
  })

  // Get last log type (OUT if no logs)
  const lastLogType = computed<'IN' | 'OUT'>(() => {
    const lastLog = todayLogs.value[todayLogs.value.length - 1]
    return lastLog?.type || 'OUT'
  })

  // Actions
  const fetchLogs = async () => {
    await refetchLogs()
  }

  const fetchSummary = async () => {
    await refetchSummary()
  }

  const submitLog = async (type: 'IN' | 'OUT') => {
    if (!employeeUuid.value) {
      throw new Error('Employee information not found')
    }

    loading.value = true
    try {
      await createMutation.mutateAsync({
        employee_uuid: employeeUuid.value,
        type,
      })
    } finally {
      loading.value = false
    }
  }

  const refreshAll = async () => {
    loading.value = true
    try {
      await Promise.all([refetchLogs(), refetchSummary()])
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    logs,
    todayLogs,
    summary,
    lastLogType,
    loading: computed(() => loading.value || isLoadingLogs.value || isLoadingSummary.value || createMutation.isPending.value),
    // Actions
    fetchLogs,
    fetchSummary,
    submitLog,
    refreshAll,
  }
})
