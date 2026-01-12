<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center justify-space-between flex-wrap w-100">
              <h1 class="text-h6 font-weight-bold mb-0">Attendance Management</h1>
              <div class="d-flex align-center gap-2">
                <!-- Debug info (remove in production) -->
                <v-chip
                  v-if="authStore.user"
                  color="grey"
                  size="x-small"
                  variant="outlined"
                >
                  Role: {{ authStore.user.role }}
                </v-chip>
                <v-chip
                  v-if="isAdmin"
                  color="info"
                  size="small"
                  prepend-icon="mdi-shield-account"
                  variant="tonal"
                >
                  Admin View
                </v-chip>
              </div>
            </div>
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-0">
            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
              <p class="mt-3 text-body-2 text-medium-emphasis">Loading attendance records...</p>
            </div>

            <!-- Error State -->
            <v-alert
              v-else-if="error"
              type="error"
              variant="tonal"
              class="ma-4"
              rounded="lg"
              density="compact"
            >
              <div class="d-flex align-center">
                <v-icon class="me-3">mdi-alert-circle</v-icon>
                <div class="flex-grow-1">
                  <div class="font-weight-medium">Failed to load attendance</div>
                  <div class="text-caption">{{ error.message }}</div>
                </div>
                <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
              </div>
            </v-alert>

            <!-- Attendance Table -->
            <v-data-table
              v-else
              :items="attendanceRecords"
              :headers="headers"
              :loading="isLoading"
              density="compact"
              item-key="uuid"
              class="attendance-table"
              :items-per-page="15"
              :items-per-page-options="[10, 15, 25, 50]"
              :hide-no-data="true"
              :no-data-text="''"
            >
              <template v-slot:[`body.append`]>
                <tr v-if="attendanceRecords.length === 0">
                  <td :colspan="headers.length" class="text-center py-8">
                    <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-calendar-clock</v-icon>
                    <p class="text-subtitle-1 font-weight-medium mb-1">No attendance records</p>
                    <p class="text-body-2 text-medium-emphasis">Attendance records are generated automatically from attendance logs</p>
                  </td>
                </tr>
              </template>

              <template v-slot:[`item.employee`]="{ item }">
                <div class="d-flex align-center">
                  <v-avatar size="32" color="primary" class="me-3">
                    <span class="text-caption font-weight-bold text-white">
                      {{ getInitials(item.employee?.user) }}
                    </span>
                  </v-avatar>
                  <div>
                    <div class="text-body-2 font-weight-medium">
                      {{ item.employee?.user?.first_name }} {{ item.employee?.user?.last_name }}
                    </div>
                    <div class="text-caption text-medium-emphasis">
                      {{ item.employee?.employee_no }}
                    </div>
                  </div>
                </div>
              </template>

              <template v-slot:[`item.date`]="{ item }">
                {{ formatDate(item.date) }}
              </template>

              <template v-slot:[`item.hours_worked`]="{ item }">
                <span class="font-weight-medium">{{ item.hours_worked ? `${item.hours_worked} hrs` : '-' }}</span>
              </template>

              <template v-slot:[`item.status`]="{ item }">
                <v-chip
                  :color="getStatusColor(item.status)"
                  size="small"
                  :prepend-icon="getStatusIcon(item.status)"
                  variant="tonal"
                >
                  {{ formatStatus(item.status) }}
                </v-chip>
              </template>

              <template v-slot:[`item.flags`]="{ item }">
                <div class="d-flex align-center gap-2 flex-wrap">
                  <v-chip
                    v-if="item.is_locked"
                    size="x-small"
                    color="grey"
                    variant="flat"
                    prepend-icon="mdi-lock"
                  >
                    Locked
                  </v-chip>
                  <v-chip
                    v-if="item.is_auto_corrected"
                    size="x-small"
                    color="warning"
                    variant="flat"
                    prepend-icon="mdi-auto-fix"
                  >
                    Auto-corrected
                  </v-chip>
                  <v-chip
                    v-if="item.needs_review"
                    size="x-small"
                    color="warning"
                    variant="flat"
                    prepend-icon="mdi-alert"
                  >
                    Review
                  </v-chip>
                  <v-chip
                    v-if="item.is_incomplete"
                    size="x-small"
                    color="error"
                    variant="flat"
                    prepend-icon="mdi-incomplete-circle"
                  >
                    Incomplete
                  </v-chip>
                </div>
              </template>

              <template v-slot:[`item.actions`]="{ item }">
                <div class="d-flex align-center gap-1">
                  <!-- Employee Actions (only for non-admins) -->
                  <template v-if="!isAdmin">
                    <!-- Time In/Out Button (only if not locked and is today) -->
                    <v-btn
                      v-if="!item.is_locked && isToday(item.date)"
                      :color="getTimeButtonColor(item)"
                      size="small"
                      variant="text"
                      :prepend-icon="getTimeButtonIcon(item)"
                      @click="handleTimeInOut(item)"
                      :loading="isTimeInOutLoading(item)"
                    >
                      {{ getTimeButtonText(item) }}
                    </v-btn>

                    <!-- View Logs -->
                    <v-btn
                      icon="mdi-eye"
                      size="small"
                      variant="text"
                      @click="viewLogs(item)"
                      title="View Logs"
                    ></v-btn>

                    <!-- Request Correction (only if needs review or auto-corrected) -->
                    <v-btn
                      v-if="(item.needs_review || item.is_auto_corrected) && !item.is_locked"
                      icon="mdi-alert-circle"
                      size="small"
                      variant="text"
                      color="warning"
                      @click="requestCorrection(item)"
                      title="Request Correction"
                    ></v-btn>
                  </template>

                  <!-- Admin Actions (always show for admins) -->
                  <template v-if="isAdmin">
                    <!-- View Logs -->
                    <v-btn
                      icon="mdi-eye"
                      size="small"
                      variant="text"
                      @click="viewLogs(item)"
                      title="View Logs"
                    ></v-btn>

                    <!-- Edit Logs -->
                    <v-btn
                      v-if="!item.is_locked"
                      icon="mdi-pencil"
                      size="small"
                      variant="text"
                      color="primary"
                      @click="editLogs(item)"
                      title="Edit Logs"
                    ></v-btn>

                    <!-- Recalculate -->
                    <v-btn
                      icon="mdi-refresh"
                      size="small"
                      variant="text"
                      color="info"
                      @click="recalculate(item)"
                      :loading="isRecalculating(item)"
                      title="Recalculate"
                    ></v-btn>

                    <!-- Approve -->
                    <v-btn
                      v-if="item.is_auto_corrected && !item.is_locked"
                      icon="mdi-check-circle"
                      size="small"
                      variant="text"
                      color="success"
                      @click="approve(item)"
                      :loading="isApproving(item)"
                      title="Approve"
                    ></v-btn>

                    <!-- Mark Incomplete -->
                    <v-btn
                      v-if="!item.is_locked && item.status !== 'incomplete'"
                      icon="mdi-incomplete-circle"
                      size="small"
                      variant="text"
                      color="warning"
                      @click="markIncomplete(item)"
                      :loading="isMarkingIncomplete(item)"
                      title="Mark Incomplete"
                    ></v-btn>

                    <!-- Lock/Unlock -->
                    <v-btn
                      :icon="item.is_locked ? 'mdi-lock-open' : 'mdi-lock'"
                      size="small"
                      variant="text"
                      :color="item.is_locked ? 'success' : 'grey'"
                      @click="toggleLock(item)"
                      :loading="isLocking(item)"
                      :title="item.is_locked ? 'Unlock' : 'Lock'"
                    ></v-btn>
                  </template>
                </div>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- View Logs Dialog -->
    <v-dialog v-model="showLogsDialog" max-width="800">
      <v-card>
        <v-card-title class="d-flex align-center">
          Attendance Logs
          <v-spacer></v-spacer>
          <v-btn icon="mdi-close" variant="text" @click="showLogsDialog = false"></v-btn>
        </v-card-title>
        <v-divider></v-divider>
        <v-card-text>
          <AttendanceTimeline
            v-if="selectedAttendance"
            :employee-uuid="selectedAttendance.employee_uuid"
            :date="selectedAttendance.date"
          />
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Edit Logs Dialog (Admin Only) -->
    <v-dialog v-model="showEditLogsDialog" max-width="800">
      <v-card>
        <v-card-title class="d-flex align-center">
          Edit Attendance Logs
          <v-spacer></v-spacer>
          <v-btn icon="mdi-close" variant="text" @click="showEditLogsDialog = false"></v-btn>
        </v-card-title>
        <v-divider></v-divider>
        <v-card-text>
          <AttendanceTimeline
            v-if="selectedAttendance"
            :employee-uuid="selectedAttendance.employee_uuid"
            :date="selectedAttendance.date"
            :editable="true"
            @log-updated="handleLogUpdated"
          />
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Request Correction Dialog -->
    <v-dialog v-model="showCorrectionDialog" max-width="600">
      <v-card>
        <v-card-title>Request Correction</v-card-title>
        <v-divider></v-divider>
        <v-card-text class="pt-4">
          <v-textarea
            v-model="correctionReason"
            label="Reason for Correction"
            placeholder="Please explain why this attendance record needs correction..."
            rows="4"
            variant="outlined"
            :rules="[v => !!v || 'Reason is required', v => (v && v.length >= 10) || 'Reason must be at least 10 characters']"
          ></v-textarea>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="showCorrectionDialog = false">Cancel</v-btn>
          <v-btn
            color="primary"
            variant="flat"
            @click="submitCorrection"
            :loading="isSubmittingCorrection"
            :disabled="!correctionReason || correctionReason.length < 10"
          >
            Submit
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { Attendance } from '@/types/attendance'
import { useAttendances } from '@/composables/useAttendance'
import { useAttendanceStore } from '@/stores/attendance'
import {
  useRequestCorrection,
  useRecalculateAttendance,
  useApproveAttendance,
  useMarkIncomplete,
  useLockAttendance,
} from '@/composables/useAttendanceManagement'
import { useNotification } from '@/composables/useNotification'
import { useAuthStore } from '@/stores/auth'
import AttendanceTimeline from './AttendanceTimeline.vue'

const authStore = useAuthStore()
const attendanceStore = useAttendanceStore()
const { showNotification } = useNotification()

// Ensure user is loaded and check admin status
const isAdmin = computed(() => {
  const user = authStore.user
  const role = user?.role
  return role === 'admin'
})

const { data, isLoading, error, refetch } = useAttendances()
const requestCorrectionMutation = useRequestCorrection()
const recalculateMutation = useRecalculateAttendance()
const approveMutation = useApproveAttendance()
const markIncompleteMutation = useMarkIncomplete()
const lockMutation = useLockAttendance()

const attendanceRecords = computed(() => data.value?.data || [])

const showLogsDialog = ref(false)
const showEditLogsDialog = ref(false)
const showCorrectionDialog = ref(false)
const selectedAttendance = ref<Attendance | null>(null)
const correctionReason = ref('')
const loadingStates = ref<Record<string, boolean>>({})

const headers = [
  { title: 'Employee', key: 'employee', sortable: true },
  { title: 'Date', key: 'date', sortable: true },
  { title: 'Hours Worked', key: 'hours_worked', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Flags', key: 'flags', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
]

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
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

const getInitials = (user?: { first_name?: string; last_name?: string }) => {
  if (!user) return '??'
  const first = user.first_name?.charAt(0) || ''
  const last = user.last_name?.charAt(0) || ''
  return `${first}${last}`.toUpperCase() || '??'
}

const isToday = (date: string): boolean => {
  const today = new Date().toISOString().split('T')[0]
  return date === today
}

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const getTimeButtonText = (_item: Attendance): string => {
  // This would need to check the last log type - simplified for now
  return 'Time In'
}

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const getTimeButtonIcon = (_item: Attendance): string => {
  return 'mdi-login'
}

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const getTimeButtonColor = (_item: Attendance): string => {
  return 'primary'
}

const isTimeInOutLoading = (item: Attendance): boolean => {
  return loadingStates.value[`time-${item.uuid}`] || false
}

const isRecalculating = (item: Attendance): boolean => {
  return loadingStates.value[`recalc-${item.uuid}`] || recalculateMutation.isPending.value
}

const isApproving = (item: Attendance): boolean => {
  return loadingStates.value[`approve-${item.uuid}`] || approveMutation.isPending.value
}

const isMarkingIncomplete = (item: Attendance): boolean => {
  return loadingStates.value[`incomplete-${item.uuid}`] || markIncompleteMutation.isPending.value
}

const isLocking = (item: Attendance): boolean => {
  return loadingStates.value[`lock-${item.uuid}`] || lockMutation.isPending.value
}

const isSubmittingCorrection = computed(() => requestCorrectionMutation.isPending.value)

// Actions
const handleTimeInOut = async (item: Attendance) => {
  loadingStates.value[`time-${item.uuid}`] = true
  try {
    // Use the attendance store to submit log
    const nextType = 'IN' // This should be determined from last log
    await attendanceStore.submitLog(nextType)
    showNotification('Time logged successfully', 'success')
    await refetch()
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    showNotification(err?.response?.data?.message || 'Failed to log time', 'error')
  } finally {
    loadingStates.value[`time-${item.uuid}`] = false
  }
}

const viewLogs = (item: Attendance) => {
  selectedAttendance.value = item
  showLogsDialog.value = true
}

const editLogs = (item: Attendance) => {
  selectedAttendance.value = item
  showEditLogsDialog.value = true
}

const requestCorrection = (item: Attendance) => {
  selectedAttendance.value = item
  correctionReason.value = ''
  showCorrectionDialog.value = true
}

const submitCorrection = async () => {
  if (!selectedAttendance.value || !correctionReason.value) return

  try {
    await requestCorrectionMutation.mutateAsync({
      attendance_uuid: selectedAttendance.value.uuid,
      reason: correctionReason.value,
    })
    showNotification('Correction request submitted successfully', 'success')
    showCorrectionDialog.value = false
    await refetch()
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    showNotification(err?.response?.data?.message || 'Failed to submit correction request', 'error')
  }
}

const recalculate = async (item: Attendance) => {
  loadingStates.value[`recalc-${item.uuid}`] = true
  try {
    await recalculateMutation.mutateAsync(item.uuid)
    showNotification('Attendance recalculated successfully', 'success')
    await refetch()
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    showNotification(err?.response?.data?.message || 'Failed to recalculate', 'error')
  } finally {
    loadingStates.value[`recalc-${item.uuid}`] = false
  }
}

const approve = async (item: Attendance) => {
  loadingStates.value[`approve-${item.uuid}`] = true
  try {
    await approveMutation.mutateAsync(item.uuid)
    showNotification('Attendance approved successfully', 'success')
    await refetch()
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    showNotification(err?.response?.data?.message || 'Failed to approve', 'error')
  } finally {
    loadingStates.value[`approve-${item.uuid}`] = false
  }
}

const markIncomplete = async (item: Attendance) => {
  loadingStates.value[`incomplete-${item.uuid}`] = true
  try {
    await markIncompleteMutation.mutateAsync(item.uuid)
    showNotification('Attendance marked as incomplete', 'success')
    await refetch()
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    showNotification(err?.response?.data?.message || 'Failed to mark as incomplete', 'error')
  } finally {
    loadingStates.value[`incomplete-${item.uuid}`] = false
  }
}

const toggleLock = async (item: Attendance) => {
  loadingStates.value[`lock-${item.uuid}`] = true
  try {
    await lockMutation.mutateAsync(item.uuid)
    showNotification(
      item.is_locked ? 'Attendance unlocked successfully' : 'Attendance locked successfully',
      'success'
    )
    await refetch()
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    showNotification(err?.response?.data?.message || 'Failed to toggle lock', 'error')
  } finally {
    loadingStates.value[`lock-${item.uuid}`] = false
  }
}

const handleLogUpdated = () => {
  refetch()
}
</script>

<style scoped>
.attendance-table {
  border-radius: 8px;
  overflow: hidden;
}

.gap-1 {
  gap: 4px;
}

.gap-2 {
  gap: 8px;
}
</style>
