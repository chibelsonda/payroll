<template>
  <v-card elevation="2" rounded="lg">
    <v-card-title class="px-4 py-3">
      <div class="d-flex align-center justify-space-between w-100">
        <div class="d-flex align-center">
          <v-icon class="me-2" color="primary">mdi-timeline-clock-outline</v-icon>
          <h2 class="text-h6 font-weight-bold mb-0">Attendance Timeline</h2>
        </div>
        <v-chip size="small" variant="tonal" color="primary">
          {{ selectedDate }}
        </v-chip>
      </div>
    </v-card-title>

    <v-divider></v-divider>

    <v-card-text class="pa-4">
      <!-- Date Selector -->
      <v-text-field
        v-model="selectedDate"
        type="date"
        label="Select Date"
        density="compact"
        variant="outlined"
        prepend-inner-icon="mdi-calendar"
        class="mb-4"
      ></v-text-field>

      <!-- Loading State -->
      <div v-if="isLoading" class="text-center py-8">
        <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
        <p class="mt-3 text-body-2 text-medium-emphasis">Loading attendance logs...</p>
      </div>

      <!-- Error State -->
      <v-alert
        v-else-if="error"
        type="error"
        variant="tonal"
        rounded="lg"
        density="compact"
        class="mb-4"
      >
        <div class="font-weight-medium">Failed to load logs</div>
        <div class="text-caption">{{ error.message }}</div>
      </v-alert>

      <!-- Timeline -->
      <div v-else-if="sortedLogs.length > 0" class="timeline-container">
        <div
          v-for="(log, index) in sortedLogs"
          :key="log.uuid"
          class="timeline-item"
        >
          <div class="d-flex align-start">
            <!-- Timeline Line -->
            <div class="timeline-line-container">
              <v-avatar
                :color="log.type === 'IN' ? 'success' : 'error'"
                size="40"
                :variant="log.is_auto_corrected ? 'outlined' : 'flat'"
              >
                <v-icon :color="log.type === 'IN' ? 'white' : 'white'">
                  {{ log.type === 'IN' ? 'mdi-login' : 'mdi-logout' }}
                </v-icon>
              </v-avatar>
              <div
                v-if="index < sortedLogs.length - 1"
                class="timeline-line"
                :class="{ 'auto-corrected': log.is_auto_corrected }"
              ></div>
            </div>

            <!-- Timeline Content -->
            <div class="timeline-content flex-grow-1 ms-3">
              <div class="d-flex align-center justify-space-between">
                <div>
                  <div class="text-body-1 font-weight-medium">
                    {{ log.type === 'IN' ? 'Time In' : 'Time Out' }}
                  </div>
                  <div v-if="!props.editable || editingLogUuid !== log.uuid" class="text-body-2 text-medium-emphasis">
                    {{ formatTime(log.log_time) }}
                  </div>
                  <div v-else class="d-flex align-center gap-2">
                    <v-text-field
                      v-model="editingLogTime"
                      type="datetime-local"
                      density="compact"
                      variant="outlined"
                      hide-details
                      style="max-width: 200px;"
                    ></v-text-field>
                    <v-select
                      v-model="editingLogType"
                      :items="[{ title: 'Time In', value: 'IN' }, { title: 'Time Out', value: 'OUT' }]"
                      density="compact"
                      variant="outlined"
                      hide-details
                      style="max-width: 120px;"
                    ></v-select>
                    <v-btn
                      icon="mdi-check"
                      size="small"
                      color="success"
                      variant="text"
                      @click="saveLogEdit(log)"
                      :loading="savingLogUuid === log.uuid"
                    ></v-btn>
                    <v-btn
                      icon="mdi-close"
                      size="small"
                      color="error"
                      variant="text"
                      @click="cancelLogEdit"
                    ></v-btn>
                  </div>
                </div>
                <div class="d-flex align-center gap-2">
                  <v-chip
                    v-if="log.is_auto_corrected"
                    size="small"
                    color="warning"
                    variant="tonal"
                    prepend-icon="mdi-alert"
                  >
                    Auto-corrected
                  </v-chip>
                  <v-btn
                    v-if="props.editable && !log.is_auto_corrected && editingLogUuid !== log.uuid"
                    icon="mdi-pencil"
                    size="small"
                    variant="text"
                    color="primary"
                    @click="startEditLog(log)"
                    title="Edit Log"
                  ></v-btn>
                  <v-btn
                    v-if="canDeleteLog(log) && editingLogUuid !== log.uuid"
                    icon="mdi-delete"
                    size="small"
                    variant="text"
                    color="error"
                    @click="deleteLog(log)"
                    :loading="deletingLogUuid === log.uuid"
                  ></v-btn>
                </div>
              </div>

              <!-- Correction Reason Tooltip -->
              <v-tooltip
                v-if="log.is_auto_corrected && log.correction_reason"
                location="top"
              >
                <template #activator="{ props }">
                  <div v-bind="props" class="text-caption text-warning mt-1">
                    <v-icon size="small" class="me-1">mdi-information-outline</v-icon>
                    {{ log.correction_reason }}
                  </div>
                </template>
                <span>{{ log.correction_reason }}</span>
              </v-tooltip>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-8">
        <v-icon size="64" color="grey-lighten-1" class="mb-3">mdi-clock-outline</v-icon>
        <p class="text-subtitle-1 font-weight-medium mb-1">No logs for this date</p>
        <p class="text-body-2 text-medium-emphasis">No attendance logs recorded yet</p>
      </div>

      <!-- Summary Info -->
      <v-card
        v-if="attendanceSummary"
        variant="tonal"
        :color="getStatusColor(attendanceSummary.status)"
        class="mt-4"
        rounded="lg"
      >
        <v-card-text class="py-3">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis">Total Hours</div>
              <div class="text-h6 font-weight-bold">{{ attendanceSummary.hours_worked }} hrs</div>
            </div>
            <v-chip
              :color="getStatusColor(attendanceSummary.status)"
              size="small"
              :prepend-icon="getStatusIcon(attendanceSummary.status)"
            >
              {{ formatStatus(attendanceSummary.status) }}
            </v-chip>
          </div>
        </v-card-text>
      </v-card>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useAttendanceLogs, useDeleteAttendanceLog } from '@/composables/useAttendanceLogs'
import { useAttendanceSummary } from '@/composables/useAttendanceSummary'
import { useUpdateAttendanceLog } from '@/composables/useAttendanceManagement'
import { useNotification } from '@/composables/useNotification'
import { useAuthStore } from '@/stores/auth'
import { formatDateTimeForBackend, formatDateTimeForInput } from '@/lib/datetime'
import type { AttendanceLog } from '@/types/attendanceLog'

const props = withDefaults(defineProps<{
  employeeUuid?: string
  date?: string
  editable?: boolean
}>(), {
  editable: false,
})

const emit = defineEmits<{
  'log-updated': []
}>()

const authStore = useAuthStore()
const deleteMutation = useDeleteAttendanceLog()
const updateLogMutation = useUpdateAttendanceLog()
const { showNotification } = useNotification()

const employeeUuid = computed(() => props.employeeUuid || authStore.user?.employee?.uuid)
const selectedDate = ref(props.date || new Date().toISOString().split('T')[0])
const deletingLogUuid = ref<string | null>(null)
const editingLogUuid = ref<string | null>(null)
const editingLogTime = ref('')
const editingLogType = ref<'IN' | 'OUT'>('IN')
const savingLogUuid = ref<string | null>(null)

const { data: logsData, isLoading, error, refetch: refetchLogs } = useAttendanceLogs(
  employeeUuid,
  selectedDate
)

const { data: summaryData, refetch: refetchSummary } = useAttendanceSummary(
  employeeUuid,
  selectedDate,
  selectedDate
)

const logs = computed(() => logsData.value || [])
const attendanceSummary = computed(() => {
  const summaries = summaryData.value || []
  return summaries.find(s => s.date === selectedDate.value) || null
})

const sortedLogs = computed(() => {
  if (!selectedDate.value) return []
  return [...logs.value]
    .filter(log => log.log_time.startsWith(selectedDate.value!))
    .sort((a, b) => new Date(a.log_time).getTime() - new Date(b.log_time).getTime())
})

const formatTime = (dateTime: string): string => {
  return new Date(dateTime).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

const formatStatus = (status: string): string => {
  const statusMap: Record<string, string> = {
    present: 'Present',
    absent: 'Absent',
    leave: 'On Leave',
    incomplete: 'Incomplete',
    needs_review: 'Needs Review',
  }
  return statusMap[status] || status
}

const getStatusColor = (status: string): string => {
  const colorMap: Record<string, string> = {
    present: 'success',
    absent: 'error',
    leave: 'info',
    incomplete: 'warning',
    needs_review: 'warning',
  }
  return colorMap[status] || 'default'
}

const getStatusIcon = (status: string): string => {
  const iconMap: Record<string, string> = {
    present: 'mdi-check-circle',
    absent: 'mdi-close-circle',
    leave: 'mdi-calendar-remove',
    incomplete: 'mdi-alert-circle',
    needs_review: 'mdi-alert',
  }
  return iconMap[status] || 'mdi-circle'
}

const canDeleteLog = (log: AttendanceLog): boolean => {
  // Only allow deletion of non-auto-corrected logs, or admin can delete any
  return authStore.isAdmin || !log.is_auto_corrected
}

const deleteLog = async (log: AttendanceLog) => {
  if (!confirm('Are you sure you want to delete this log?')) {
    return
  }

  deletingLogUuid.value = log.uuid
  try {
    await deleteMutation.mutateAsync(log.uuid)
    showNotification('Attendance log deleted successfully', 'success')
    await refetchLogs()
    await refetchSummary()
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    const message = err?.response?.data?.message || 'Failed to delete log'
    showNotification(message, 'error')
  } finally {
    deletingLogUuid.value = null
  }
}

const startEditLog = (log: AttendanceLog) => {
  editingLogUuid.value = log.uuid
  // Use helper function to format backend datetime for datetime-local input
  editingLogTime.value = formatDateTimeForInput(log.log_time)
  editingLogType.value = log.type
}

const cancelLogEdit = () => {
  editingLogUuid.value = null
  editingLogTime.value = ''
  editingLogType.value = 'IN'
}

const saveLogEdit = async (log: AttendanceLog) => {
  if (!editingLogTime.value || !editingLogType.value) return

  savingLogUuid.value = log.uuid
  try {
    // Use helper function to format datetime-local value for backend
    const backendDateTime = formatDateTimeForBackend(editingLogTime.value)

    await updateLogMutation.mutateAsync({
      logUuid: log.uuid,
      data: {
        log_time: backendDateTime,
        type: editingLogType.value,
      },
    })
    showNotification('Attendance log updated successfully', 'success')
    editingLogUuid.value = null
    await refetchLogs()
    await refetchSummary()
    emit('log-updated')
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    showNotification(err?.response?.data?.message || 'Failed to update log', 'error')
  } finally {
    savingLogUuid.value = null
  }
}

watch(selectedDate, () => {
  refetchLogs()
  refetchSummary()
})
</script>

<style scoped>
.timeline-container {
  position: relative;
}

.timeline-item {
  margin-bottom: 16px;
}

.timeline-line-container {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.timeline-line {
  width: 2px;
  flex: 1;
  min-height: 40px;
  background-color: rgba(0, 0, 0, 0.12);
  margin-top: 4px;
}

.timeline-line.auto-corrected {
  background-color: rgba(255, 152, 0, 0.3);
  border-left: 2px dashed rgb(255, 152, 0);
}

.timeline-content {
  padding-bottom: 8px;
}

.gap-2 {
  gap: 8px;
}
</style>
