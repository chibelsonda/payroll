<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center justify-space-between flex-wrap w-100">
              <h1 class="text-h6 font-weight-bold mb-0">Attendance Management</h1>
              <v-btn
                color="primary"
                size="small"
                prepend-icon="mdi-plus"
                @click="showAttendanceForm = true"
              >
                Record Attendance
              </v-btn>
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
              :row-height="40"
            >
              <template v-slot:[`body.append`]>
                <tr v-if="attendanceRecords.length === 0">
                  <td :colspan="headers.length" class="text-center py-8">
                    <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-calendar-clock</v-icon>
                    <p class="text-subtitle-1 font-weight-medium mb-1">No attendance records</p>
                    <p class="text-body-2 text-medium-emphasis mb-3">Start by recording employee attendance</p>
                    <v-btn
                      color="primary"
                      size="small"
                      prepend-icon="mdi-plus"
                      @click="showAttendanceForm = true"
                    >
                      Record Attendance
                    </v-btn>
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

              <template v-slot:[`item.time_in`]="{ item }">
                {{ item.time_in || '-' }}
              </template>

              <template v-slot:[`item.time_out`]="{ item }">
                {{ item.time_out || '-' }}
              </template>

              <template v-slot:[`item.hours_worked`]="{ item }">
                {{ item.hours_worked ? `${item.hours_worked} hrs` : '-' }}
              </template>

              <template v-slot:[`item.actions`]="{ item }">
                <v-menu>
                  <template #activator="{ props }">
                    <v-btn
                      icon="mdi-dots-vertical"
                      size="small"
                      variant="text"
                      v-bind="props"
                      class="action-btn"
                    ></v-btn>
                  </template>
                  <v-list density="compact">
                    <v-list-item
                      prepend-icon="mdi-pencil"
                      title="Edit"
                      @click="editAttendance(item)"
                    ></v-list-item>
                    <v-list-item
                      prepend-icon="mdi-delete"
                      title="Delete"
                      @click="deleteAttendance(item)"
                    ></v-list-item>
                  </v-list>
                </v-menu>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Attendance Form Drawer -->
    <AttendanceForm
      v-model="showAttendanceForm"
      :attendance-uuid="selectedAttendanceUuid"
      @success="handleSuccess"
      @close="handleClose"
    />
  </v-container>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { Attendance } from '@/types/attendance'
import AttendanceForm from './AttendanceForm.vue'

// TODO: Replace with actual composable when backend API is ready
const isLoading = ref(false)
const error = ref<Error | null>(null)
const attendanceRecords = ref<Attendance[]>([])

const showAttendanceForm = ref(false)
const selectedAttendanceUuid = ref<string | null>(null)

const headers = [
  { title: 'Employee', key: 'employee', sortable: true },
  { title: 'Date', key: 'date', sortable: true },
  { title: 'Time In', key: 'time_in', sortable: true },
  { title: 'Time Out', key: 'time_out', sortable: true },
  { title: 'Hours Worked', key: 'hours_worked', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
]

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}

const getInitials = (user: any) => {
  if (!user) return '??'
  const first = user.first_name?.charAt(0) || ''
  const last = user.last_name?.charAt(0) || ''
  return `${first}${last}`.toUpperCase() || '??'
}

const editAttendance = (attendance: Attendance) => {
  selectedAttendanceUuid.value = attendance.uuid
  showAttendanceForm.value = true
}

const deleteAttendance = (attendance: Attendance) => {
  // TODO: Implement delete
  console.log('Delete attendance', attendance)
}

const handleSuccess = () => {
  showAttendanceForm.value = false
  selectedAttendanceUuid.value = null
  // TODO: Refetch data
}

const handleClose = () => {
  showAttendanceForm.value = false
  selectedAttendanceUuid.value = null
}

const refetch = () => {
  // TODO: Implement refetch
}
</script>

<style scoped>
.attendance-table {
  border-radius: 8px;
  overflow: hidden;
}

.attendance-table :deep(.v-data-table__thead th) {
  background-color: rgba(0, 0, 0, 0.03);
  font-weight: 600;
  font-size: 0.875rem;
  padding: 14px 16px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: rgba(0, 0, 0, 0.87);
}

.attendance-table :deep(.v-data-table__tbody td) {
  padding: 14px 16px;
  font-size: 0.875rem;
}

.attendance-table :deep(.v-data-table__tbody tr:hover) {
  background-color: rgba(25, 118, 210, 0.04);
}

.action-btn {
  opacity: 0.7;
}

.action-btn:hover {
  opacity: 1;
}
</style>
