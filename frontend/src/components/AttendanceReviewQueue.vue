<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center justify-space-between flex-wrap w-100">
              <div class="d-flex align-center">
                <v-icon class="me-2" color="warning">mdi-alert-circle-outline</v-icon>
                <h1 class="text-h6 font-weight-bold mb-0">Attendance Review Queue</h1>
                <v-chip
                  v-if="attendancesNeedingReview.length > 0"
                  color="warning"
                  size="small"
                  class="ms-3"
                >
                  {{ attendancesNeedingReview.length }} pending
                </v-chip>
              </div>
              <v-btn
                icon="mdi-refresh"
                size="small"
                variant="text"
                @click="refetch"
                :loading="isLoading"
              ></v-btn>
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
              :items="attendancesNeedingReview"
              :headers="headers"
              density="compact"
              item-key="uuid"
              class="attendance-review-table"
              :items-per-page="15"
              :items-per-page-options="[10, 15, 25, 50]"
              :hide-no-data="true"
            >
              <template v-slot:[`body.append`]>
                <tr v-if="attendancesNeedingReview.length === 0">
                  <td :colspan="headers.length" class="text-center py-8">
                    <v-icon size="48" color="success" class="mb-3">mdi-check-circle-outline</v-icon>
                    <p class="text-subtitle-1 font-weight-medium mb-1">All clear!</p>
                    <p class="text-body-2 text-medium-emphasis">No attendance records need review</p>
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
                <span class="font-weight-medium">{{ item.hours_worked }} hrs</span>
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
                    v-if="item.is_auto_corrected"
                    size="x-small"
                    color="warning"
                    variant="flat"
                    prepend-icon="mdi-auto-fix"
                  >
                    Auto-corrected
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
                  <v-chip
                    v-if="item.needs_review"
                    size="x-small"
                    color="warning"
                    variant="flat"
                    prepend-icon="mdi-alert"
                  >
                    Review
                  </v-chip>
                </div>
              </template>

              <template v-slot:[`item.actions`]="{ item }">
                <div class="d-flex align-center gap-2">
                  <v-btn
                    icon="mdi-eye"
                    size="small"
                    variant="text"
                    @click="viewDetails(item)"
                  ></v-btn>
                  <v-btn
                    icon="mdi-refresh"
                    size="small"
                    variant="text"
                    color="primary"
                    @click="fixAttendance(item)"
                    :loading="fixingUuid === item.uuid"
                    title="Recalculate"
                  ></v-btn>
                  <v-btn
                    icon="mdi-check-circle"
                    size="small"
                    variant="text"
                    color="success"
                    @click="resolveAttendance(item)"
                    :loading="resolvingUuid === item.uuid"
                    title="Mark as Resolved"
                  ></v-btn>
                </div>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Attendance Details Dialog -->
    <v-dialog v-model="showDetailsDialog" max-width="800" persistent>
      <v-card>
        <v-card-title class="px-4 py-3">
          <div class="d-flex align-center justify-space-between w-100">
            <div class="d-flex align-center">
              <v-icon class="me-2" color="primary">mdi-calendar-clock</v-icon>
              <h2 class="text-h6 font-weight-bold mb-0">Attendance Details</h2>
            </div>
            <v-btn
              icon="mdi-close"
              size="small"
              variant="text"
              @click="showDetailsDialog = false"
            ></v-btn>
          </div>
        </v-card-title>

        <v-divider></v-divider>

        <v-card-text class="pa-4">
          <div v-if="selectedAttendance">
            <v-row dense class="mb-4">
              <v-col cols="12" sm="6">
                <div class="text-caption text-medium-emphasis">Employee</div>
                <div class="text-body-1 font-weight-medium">
                  {{ selectedAttendance.employee?.user?.first_name }}
                  {{ selectedAttendance.employee?.user?.last_name }}
                </div>
              </v-col>
              <v-col cols="12" sm="6">
                <div class="text-caption text-medium-emphasis">Date</div>
                <div class="text-body-1 font-weight-medium">
                  {{ formatDate(selectedAttendance.date) }}
                </div>
              </v-col>
              <v-col cols="12" sm="6">
                <div class="text-caption text-medium-emphasis">Hours Worked</div>
                <div class="text-body-1 font-weight-medium">
                  {{ selectedAttendance.hours_worked }} hrs
                </div>
              </v-col>
              <v-col cols="12" sm="6">
                <div class="text-caption text-medium-emphasis">Status</div>
                <v-chip
                  :color="getStatusColor(selectedAttendance.status)"
                  size="small"
                  :prepend-icon="getStatusIcon(selectedAttendance.status)"
                  variant="tonal"
                >
                  {{ formatStatus(selectedAttendance.status) }}
                </v-chip>
              </v-col>
            </v-row>

            <!-- Attendance Timeline -->
            <div class="mt-4">
              <h3 class="text-subtitle-1 font-weight-bold mb-3">Attendance Timeline</h3>
              <AttendanceTimeline
                :employee-uuid="selectedAttendance.employee_uuid"
                :date="selectedAttendance.date"
              />
            </div>
          </div>
        </v-card-text>

        <v-card-actions class="px-4 py-3">
          <v-spacer></v-spacer>
          <v-btn
            variant="text"
            @click="showDetailsDialog = false"
          >
            Close
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAttendanceNeedingReview, useResolveAttendance, useFixAttendance } from '@/composables/useAttendanceReview'
import { useNotification } from '@/composables/useNotification'
import AttendanceTimeline from './AttendanceTimeline.vue'
import type { Attendance } from '@/types/attendance'

const { data, isLoading, error, refetch } = useAttendanceNeedingReview()
const resolveMutation = useResolveAttendance()
const fixMutation = useFixAttendance()
const { showNotification } = useNotification()

const attendancesNeedingReview = computed(() => data.value || [])
const selectedAttendance = ref<Attendance | null>(null)
const showDetailsDialog = ref(false)
const resolvingUuid = ref<string | null>(null)
const fixingUuid = ref<string | null>(null)

const headers = [
  { title: 'Employee', key: 'employee', sortable: true },
  { title: 'Date', key: 'date', sortable: true },
  { title: 'Hours Worked', key: 'hours_worked', sortable: true, align: 'end' as const },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Flags', key: 'flags', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
]

const formatDate = (date: string): string => {
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

const getInitials = (user?: { first_name?: string; last_name?: string }): string => {
  if (!user) return '??'
  const first = user.first_name?.charAt(0).toUpperCase() || ''
  const last = user.last_name?.charAt(0).toUpperCase() || ''
  return `${first}${last}` || '??'
}

const viewDetails = (attendance: Attendance) => {
  selectedAttendance.value = attendance
  showDetailsDialog.value = true
}

const fixAttendance = async (attendance: Attendance) => {
  fixingUuid.value = attendance.uuid
  try {
    await fixMutation.mutateAsync(attendance.uuid)
    showNotification('Attendance recalculated successfully', 'success')
    await refetch()
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    const message = err?.response?.data?.message || 'Failed to recalculate attendance'
    showNotification(message, 'error')
  } finally {
    fixingUuid.value = null
  }
}

const resolveAttendance = async (attendance: Attendance) => {
  resolvingUuid.value = attendance.uuid
  try {
    await resolveMutation.mutateAsync(attendance.uuid)
    showNotification('Attendance marked as resolved', 'success')
    await refetch()
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    const message = err?.response?.data?.message || 'Failed to resolve attendance'
    showNotification(message, 'error')
  } finally {
    resolvingUuid.value = null
  }
}
</script>

<style scoped>
.attendance-review-table {
  border-radius: 8px;
  overflow: hidden;
}

.gap-2 {
  gap: 8px;
}
</style>
