<template>
  <v-card elevation="2" rounded="lg">
    <v-card-title class="px-4 py-3">
      <div class="d-flex align-center justify-space-between w-100">
        <div class="d-flex align-center">
          <v-icon class="me-2" color="primary">mdi-calendar-multiple-check</v-icon>
          <h2 class="text-h6 font-weight-bold mb-0">Attendance Summary</h2>
        </div>
        <v-btn
          v-if="showDateRange"
          icon="mdi-refresh"
          size="small"
          variant="text"
          @click="refetch"
          :loading="isLoading"
        ></v-btn>
      </div>
    </v-card-title>

    <v-divider></v-divider>

    <v-card-text class="pa-4">
      <!-- Date Range Selector (only show if showDateRange prop is true) -->
      <v-row v-if="showDateRange" dense class="mb-4">
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="fromDate"
            type="date"
            label="From Date"
            density="compact"
            variant="outlined"
            prepend-inner-icon="mdi-calendar-start"
          ></v-text-field>
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="toDate"
            type="date"
            label="To Date"
            density="compact"
            variant="outlined"
            prepend-inner-icon="mdi-calendar-end"
          ></v-text-field>
        </v-col>
      </v-row>

      <!-- Loading State -->
      <div v-if="isLoading" class="text-center py-8">
        <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
        <p class="mt-3 text-body-2 text-medium-emphasis">Loading attendance summary...</p>
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
        <div class="font-weight-medium">Failed to load summary</div>
        <div class="text-caption">{{ error.message }}</div>
      </v-alert>

      <!-- Single Day Summary Card (when showDateRange is false) -->
      <template v-else-if="!showDateRange">
        <!-- Warning for needs_review -->
        <v-alert
          v-if="summary && summary.needs_review"
          type="warning"
          variant="tonal"
          rounded="lg"
          density="compact"
          class="mb-4"
        >
          <div class="font-weight-medium">⚠ This attendance record needs review</div>
          <div class="text-caption mt-1">Please contact your administrator to resolve any issues.</div>
        </v-alert>

        <!-- Summary Card -->
        <v-card
          v-if="summary"
          variant="tonal"
          :color="getStatusColor(summary.status)"
          rounded="lg"
        >
          <v-card-text class="py-4">
            <v-row align="center">
              <v-col cols="12" sm="6">
                <div class="text-caption text-medium-emphasis mb-1">Total Hours Worked</div>
                <div class="text-h5 font-weight-bold">{{ summary.hours_worked }} hrs</div>
              </v-col>
              <v-col cols="12" sm="6" class="text-sm-right">
                <v-chip
                  :color="getStatusColor(summary.status)"
                  size="small"
                  :prepend-icon="getStatusIcon(summary.status)"
                  variant="flat"
                >
                  {{ formatStatus(summary.status) }}
                </v-chip>
              </v-col>
            </v-row>
            
            <!-- Flags -->
            <div v-if="summary.is_auto_corrected || summary.needs_review || summary.is_incomplete" class="mt-3 d-flex align-center gap-2 flex-wrap">
              <v-chip
                v-if="summary.is_auto_corrected"
                size="x-small"
                color="warning"
                variant="flat"
                prepend-icon="mdi-auto-fix"
              >
                Auto-corrected
              </v-chip>
              <v-chip
                v-if="summary.needs_review"
                size="x-small"
                color="warning"
                variant="flat"
                prepend-icon="mdi-alert"
              >
                Needs Review
              </v-chip>
              <v-chip
                v-if="summary.is_incomplete"
                size="x-small"
                color="error"
                variant="flat"
                prepend-icon="mdi-incomplete-circle"
              >
                Incomplete
              </v-chip>
            </div>
          </v-card-text>
        </v-card>

        <!-- Empty State for single day -->
        <div v-else class="text-center py-8">
          <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-calendar-clock</v-icon>
          <p class="text-subtitle-1 font-weight-medium mb-1">No attendance record for today</p>
          <p class="text-body-2 text-medium-emphasis">Clock in to start recording attendance</p>
        </div>
      </template>

      <!-- Summary Table (for date range view) -->
      <template v-else>
        <v-data-table
          :items="summaries"
          :headers="headers"
          density="compact"
          item-key="uuid"
          class="attendance-summary-table"
          :items-per-page="15"
          :items-per-page-options="[10, 15, 25, 50]"
          :hide-no-data="true"
        >
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
            <div class="d-flex align-center gap-2">
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

          <template v-slot:[`body.append`]>
            <tr v-if="summaries.length === 0">
              <td :colspan="headers.length" class="text-center py-8">
                <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-calendar-clock</v-icon>
                <p class="text-subtitle-1 font-weight-medium mb-1">No attendance records</p>
                <p class="text-body-2 text-medium-emphasis">No attendance data for the selected period</p>
              </td>
            </tr>
          </template>
        </v-data-table>
      </template>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useAttendanceSummary } from '@/composables/useAttendanceSummary'
import { useAttendanceStore } from '@/stores/attendance'
import { useAuthStore } from '@/stores/auth'
import type { Attendance } from '@/types/attendance'

const props = withDefaults(defineProps<{
  employeeUuid?: string
  showDateRange?: boolean
}>(), {
  showDateRange: false,
})

const authStore = useAuthStore()
const attendanceStore = useAttendanceStore()
const employeeUuid = computed(() => props.employeeUuid || authStore.user?.employee?.uuid)

// For single day view, use today's summary from store
const summary = computed(() => {
  if (!props.showDateRange) {
    return attendanceStore.summary
  }
  return null
})

// For date range view
const today = computed(() => new Date().toISOString().split('T')[0])
const firstDay = new Date()
firstDay.setDate(1) // First day of current month
const fromDate = ref(firstDay.toISOString().split('T')[0])
const toDate = ref(today.value)

const { data, isLoading, error, refetch } = useAttendanceSummary(
  employeeUuid,
  props.showDateRange ? fromDate : undefined,
  props.showDateRange ? toDate : undefined
)

const summaries = computed(() => {
  if (!props.showDateRange) return []
  const dataList = data.value || []
  return dataList.sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
})

const headers = [
  { title: 'Date', key: 'date', sortable: true },
  { title: 'Hours Worked', key: 'hours_worked', sortable: true, align: 'end' as const },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Flags', key: 'flags', sortable: false },
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

watch([fromDate, toDate], () => {
  if (props.showDateRange) {
    refetch()
  }
})
</script>

<style scoped>
.attendance-summary-table {
  border-radius: 8px;
  overflow: hidden;
}

.gap-2 {
  gap: 8px;
}

.text-sm-right {
  text-align: right;
}

@media (max-width: 600px) {
  .text-sm-right {
    text-align: left;
    margin-top: 8px;
  }
}
</style>
