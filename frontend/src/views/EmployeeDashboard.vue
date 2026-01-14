<template>
  <v-container fluid class="pa-6">
    <!-- Welcome Header -->
    <v-row>
      <v-col cols="12">
        <div class="text-h4 font-weight-bold mb-2">Welcome back, {{ fullName }}!</div>
        <div class="text-body-1 text-medium-emphasis mb-6">
          Here's your dashboard overview for today.
        </div>
      </v-col>
    </v-row>

    <!-- Quick Stats Cards -->
    <v-row>
      <!-- Today's Status Card -->
      <v-col cols="12" sm="6" md="4">
        <v-card
          class="stat-card"
          elevation="2"
          rounded="lg"
        >
          <v-card-text class="pa-4">
            <div class="d-flex align-center justify-space-between mb-3">
              <v-icon size="40" :color="attendanceStatusColor">{{ attendanceStatusIcon }}</v-icon>
              <v-chip
                :color="attendanceStatusColor"
                size="small"
                variant="flat"
              >
                {{ attendanceStatusText }}
              </v-chip>
            </div>
            <div class="text-body-2 text-medium-emphasis mb-1">Today's Status</div>
            <div class="text-h5 font-weight-bold">{{ attendanceStatusText }}</div>
            <div v-if="todayHours" class="text-caption text-medium-emphasis mt-2">
              {{ todayHours }} hours worked
            </div>
            <div v-else class="text-caption text-medium-emphasis mt-2">
              {{ lastLogTime }}
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Employee Info Card -->
      <v-col cols="12" sm="6" md="4">
        <v-card
          class="stat-card"
          elevation="2"
          rounded="lg"
        >
          <v-card-text class="pa-4">
            <div class="d-flex align-center justify-space-between mb-3">
              <v-icon size="40" color="primary">mdi-account-circle</v-icon>
            </div>
            <div class="text-body-2 text-medium-emphasis mb-1">Employee ID</div>
            <div class="text-h5 font-weight-bold">{{ auth.user?.employee?.employee_no || 'N/A' }}</div>
            <div class="text-caption text-medium-emphasis mt-2">
              {{ auth.user?.email }}
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Quick Actions Card -->
      <v-col cols="12" sm="6" md="4">
        <v-card
          class="stat-card"
          elevation="2"
          rounded="lg"
        >
          <v-card-title class="text-h6 font-weight-medium pb-2">
            Quick Actions
          </v-card-title>
          <v-card-text class="pa-4">
            <v-list density="compact" class="pa-0">
              <v-list-item
                to="/employee/attendance"
                prepend-icon="mdi-clock-outline"
                title="Time Clock"
                class="px-0"
              >
              </v-list-item>
              <v-divider class="my-2"></v-divider>
              <v-list-item
                to="/employee/attendance"
                prepend-icon="mdi-calendar-clock"
                title="View Attendance"
                class="px-0"
              >
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Today's Attendance Summary -->
    <v-row class="mt-2">
      <v-col cols="12" md="8">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center">
              <v-icon class="me-2" color="primary">mdi-calendar-today</v-icon>
              <h2 class="text-h6 font-weight-bold mb-0">Today's Attendance</h2>
            </div>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="pa-6">
            <!-- Loading State -->
            <div v-if="attendanceStore.loading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
              <p class="mt-3 text-body-2 text-medium-emphasis">Loading attendance data...</p>
            </div>

            <!-- Summary Content -->
            <div v-else-if="attendanceStore.summary">
              <v-row>
                <v-col cols="12" sm="6">
                  <div class="text-body-2 text-medium-emphasis mb-1">Total Hours Worked</div>
                  <div class="text-h4 font-weight-bold">{{ attendanceStore.summary.hours_worked || 0 }} hrs</div>
                </v-col>
                <v-col cols="12" sm="6">
                  <div class="text-body-2 text-medium-emphasis mb-1">Status</div>
                  <v-chip
                    :color="getStatusColor(attendanceStore.summary.status)"
                    size="small"
                    :prepend-icon="getStatusIcon(attendanceStore.summary.status)"
                    variant="flat"
                  >
                    {{ getStatusText(attendanceStore.summary.status) }}
                  </v-chip>
                </v-col>
              </v-row>
              <v-row class="mt-4">
                <v-col cols="12" sm="6">
                  <div class="text-body-2 text-medium-emphasis mb-1">Time In</div>
                  <div class="text-body-1 font-weight-medium">
                    {{ formatTime(attendanceStore.summary.time_in) || 'Not recorded' }}
                  </div>
                </v-col>
                <v-col cols="12" sm="6">
                  <div class="text-body-2 text-medium-emphasis mb-1">Time Out</div>
                  <div class="text-body-1 font-weight-medium">
                    {{ formatTime(attendanceStore.summary.time_out) || 'Not recorded' }}
                  </div>
                </v-col>
              </v-row>
            </div>

            <!-- No Data State -->
            <div v-else class="text-center py-8">
              <v-icon size="64" color="grey-lighten-1" class="mb-3">mdi-calendar-blank</v-icon>
              <p class="text-body-1 text-medium-emphasis">No attendance record for today</p>
              <v-btn
                to="/employee/attendance"
                color="primary"
                variant="flat"
                class="mt-4"
                prepend-icon="mdi-clock-outline"
              >
                Go to Time Clock
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Profile Card -->
      <v-col cols="12" md="4">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center">
              <v-icon class="me-2" color="primary">mdi-account</v-icon>
              <h2 class="text-h6 font-weight-bold mb-0">My Profile</h2>
            </div>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="pa-4">
            <v-list density="compact" class="pa-0">
              <v-list-item class="px-0">
                <v-list-item-title class="text-body-2 text-medium-emphasis">Name</v-list-item-title>
                <v-list-item-subtitle class="text-body-1 font-weight-medium">{{ fullName }}</v-list-item-subtitle>
              </v-list-item>
              <v-divider class="my-2"></v-divider>
              <v-list-item class="px-0">
                <v-list-item-title class="text-body-2 text-medium-emphasis">Email</v-list-item-title>
                <v-list-item-subtitle class="text-body-1 font-weight-medium">{{ auth.user?.email }}</v-list-item-subtitle>
              </v-list-item>
              <v-divider class="my-2"></v-divider>
              <v-list-item class="px-0">
                <v-list-item-title class="text-body-2 text-medium-emphasis">Employee ID</v-list-item-title>
                <v-list-item-subtitle class="text-body-1 font-weight-medium">
                  {{ auth.user?.employee?.employee_no || 'N/A' }}
                </v-list-item-subtitle>
              </v-list-item>
            </v-list>
            <v-btn
              to="/employee/profile"
              color="primary"
              variant="flat"
              block
              class="mt-4"
              prepend-icon="mdi-account-edit"
            >
              View Profile
            </v-btn>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useAttendanceStore } from '@/stores/attendance'

const auth = useAuthStore()
const attendanceStore = useAttendanceStore()

const fullName = computed(() => {
  const user = auth.user
  if (!user) return 'Guest'
  if (user.first_name && user.last_name) {
    return `${user.first_name} ${user.last_name}`
  }
  return user.email
})

const attendanceStatusText = computed(() => {
  return attendanceStore.lastLogType === 'IN' ? 'Clocked In' : 'Clocked Out'
})

const attendanceStatusColor = computed(() => {
  return attendanceStore.lastLogType === 'IN' ? 'success' : 'default'
})

const attendanceStatusIcon = computed(() => {
  return attendanceStore.lastLogType === 'IN' ? 'mdi-account-clock' : 'mdi-account-clock-outline'
})

const todayHours = computed(() => {
  return attendanceStore.summary?.hours_worked || null
})

const lastLogTime = computed(() => {
  const lastLog = attendanceStore.todayLogs[attendanceStore.todayLogs.length - 1]
  if (!lastLog) return 'No logs today'
  const time = new Date(lastLog.log_time).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
  })
  return `Last ${lastLog.type} at ${time}`
})

const formatTime = (timeString: string | null | undefined): string => {
  if (!timeString) return ''
  try {
    const date = new Date(timeString)
    return date.toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return ''
  }
}

const getStatusColor = (status: string | null | undefined): string => {
  switch (status) {
    case 'present':
      return 'success'
    case 'incomplete':
      return 'warning'
    case 'needs_review':
      return 'warning'
    case 'absent':
      return 'error'
    case 'leave':
      return 'info'
    default:
      return 'default'
  }
}

const getStatusIcon = (status: string | null | undefined): string => {
  switch (status) {
    case 'present':
      return 'mdi-check-circle'
    case 'incomplete':
      return 'mdi-alert-circle'
    case 'needs_review':
      return 'mdi-alert'
    case 'absent':
      return 'mdi-close-circle'
    case 'leave':
      return 'mdi-calendar-remove'
    default:
      return 'mdi-help-circle'
  }
}

const getStatusText = (status: string | null | undefined): string => {
  switch (status) {
    case 'present':
      return 'Present'
    case 'incomplete':
      return 'Incomplete'
    case 'needs_review':
      return 'Needs Review'
    case 'absent':
      return 'Absent'
    case 'leave':
      return 'On Leave'
    default:
      return 'Pending'
  }
}

onMounted(() => {
  // Refresh attendance data when component mounts
  attendanceStore.refreshAll()
})
</script>

<style scoped>
.stat-card {
  transition: transform 0.2s, box-shadow 0.2s;
  height: 100%;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12) !important;
}
</style>
