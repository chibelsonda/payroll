<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center justify-space-between flex-wrap w-100">
              <h1 class="text-h6 font-weight-bold mb-0">Leave Requests</h1>
              <v-btn
                color="primary"
                size="small"
                prepend-icon="mdi-plus"
                @click="showLeaveRequestForm = true"
              >
                Request Leave
              </v-btn>
            </div>
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-0">
            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
              <p class="mt-3 text-body-2 text-medium-emphasis">Loading leave requests...</p>
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
                  <div class="font-weight-medium">Failed to load leave requests</div>
                  <div class="text-caption">{{ error.message }}</div>
                </div>
                <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
              </div>
            </v-alert>

            <!-- Leave Requests Table -->
            <v-data-table
              v-else
              :items="leaveRequests"
              :headers="headers"
              :loading="isLoading"
              density="compact"
              item-key="uuid"
              class="leave-requests-table"
              :items-per-page="15"
              :items-per-page-options="[10, 15, 25, 50]"
              :hide-no-data="true"
              :no-data-text="''"
              :row-height="40"
            >
              <template v-slot:[`body.append`]>
                <tr v-if="leaveRequests.length === 0">
                  <td :colspan="headers.length" class="text-center py-8">
                    <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-calendar-remove</v-icon>
                    <p class="text-subtitle-1 font-weight-medium mb-1">No leave requests</p>
                    <p class="text-body-2 text-medium-emphasis mb-3">Start by submitting a leave request</p>
                    <v-btn
                      color="primary"
                      size="small"
                      prepend-icon="mdi-plus"
                      @click="showLeaveRequestForm = true"
                    >
                      Request Leave
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

              <template v-slot:[`item.leave_type`]="{ item }">
                <v-chip size="small" :color="item.leave_type === 'vacation' ? 'blue' : 'orange'">
                  {{ item.leave_type }}
                </v-chip>
              </template>

              <template v-slot:[`item.start_date`]="{ item }">
                {{ formatDate(item.start_date) }}
              </template>

              <template v-slot:[`item.end_date`]="{ item }">
                {{ formatDate(item.end_date) }}
              </template>

              <template v-slot:[`item.status`]="{ item }">
                <v-chip
                  size="small"
                  :color="getStatusColor(item.status)"
                  variant="tonal"
                >
                  {{ item.status }}
                </v-chip>
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
                      v-if="item.status === 'pending'"
                      prepend-icon="mdi-check"
                      title="Approve"
                      @click="approveLeaveRequest(item)"
                    ></v-list-item>
                    <v-list-item
                      v-if="item.status === 'pending'"
                      prepend-icon="mdi-close"
                      title="Reject"
                      @click="rejectLeaveRequest(item)"
                    ></v-list-item>
                    <v-list-item
                      prepend-icon="mdi-eye"
                      title="View"
                      @click="viewLeaveRequest(item)"
                    ></v-list-item>
                  </v-list>
                </v-menu>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Leave Request Form Drawer -->
    <LeaveRequestForm
      v-model="showLeaveRequestForm"
      @success="handleSuccess"
      @close="handleClose"
    />
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { LeaveRequest } from '@/types/leave'
import LeaveRequestForm from './LeaveRequestForm.vue'
import { useLeaveRequests, useApproveLeaveRequest, useRejectLeaveRequest } from '@/composables'
import { useNotification } from '@/composables'

const { data, isLoading, error, refetch } = useLeaveRequests()
const approveMutation = useApproveLeaveRequest()
const rejectMutation = useRejectLeaveRequest()
const { showNotification } = useNotification()

const showLeaveRequestForm = ref(false)
const selectedLeaveRequest = ref<LeaveRequest | null>(null)

const leaveRequests = computed(() => data.value?.data || [])

const headers = [
  { title: 'Employee', key: 'employee', sortable: true },
  { title: 'Leave Type', key: 'leave_type', sortable: true },
  { title: 'Start Date', key: 'start_date', sortable: true },
  { title: 'End Date', key: 'end_date', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
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

const getStatusColor = (status: string) => {
  switch (status) {
    case 'approved':
      return 'success'
    case 'rejected':
      return 'error'
    case 'pending':
      return 'warning'
    default:
      return 'default'
  }
}

const approveLeaveRequest = async (request: LeaveRequest) => {
  try {
    await approveMutation.mutateAsync(request.uuid)
    showNotification('Leave request approved successfully', 'success')
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    const message = err?.response?.data?.message || 'Failed to approve leave request'
    showNotification(message, 'error')
  }
}

const rejectLeaveRequest = async (request: LeaveRequest) => {
  try {
    await rejectMutation.mutateAsync(request.uuid)
    showNotification('Leave request rejected successfully', 'success')
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    const message = err?.response?.data?.message || 'Failed to reject leave request'
    showNotification(message, 'error')
  }
}

const viewLeaveRequest = (request: LeaveRequest) => {
  selectedLeaveRequest.value = request
  // TODO: Implement view dialog/modal if needed
  console.log('View leave request', request)
}

const handleSuccess = () => {
  showLeaveRequestForm.value = false
  refetch()
}

const handleClose = () => {
  showLeaveRequestForm.value = false
}
</script>

<style scoped>
.leave-requests-table {
  border-radius: 8px;
  overflow: hidden;
}

.leave-requests-table :deep(.v-data-table__thead th) {
  background-color: rgba(0, 0, 0, 0.03);
  font-weight: 600;
  font-size: 0.875rem;
  padding: 14px 16px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: rgba(0, 0, 0, 0.87);
}

.leave-requests-table :deep(.v-data-table__tbody td) {
  padding: 14px 16px;
  font-size: 0.875rem;
}

.leave-requests-table :deep(.v-data-table__tbody tr:hover) {
  background-color: rgba(25, 118, 210, 0.04);
}

.action-btn {
  opacity: 0.7;
}

.action-btn:hover {
  opacity: 1;
}
</style>
