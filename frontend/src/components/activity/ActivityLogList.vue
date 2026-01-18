<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <h1 class="text-h6 font-weight-bold mb-0">Activity Logs</h1>
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-0">
            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
              <p class="mt-3 text-body-2 text-medium-emphasis">Loading activity logs...</p>
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
                  <div class="font-weight-medium">Failed to load activity logs</div>
                  <div class="text-caption">{{ error.message }}</div>
                </div>
                <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
              </div>
            </v-alert>

            <!-- Activity Logs Table -->
            <v-data-table
              v-else
              v-model:page="page"
              :items="activityLogsData"
              :headers="headers"
              :loading="isLoading"
              density="compact"
              item-key="id"
              class="activity-logs-table"
              :items-per-page="15"
              :items-per-page-options="[10, 15, 25, 50]"
              :hide-no-data="true"
              :no-data-text="''"
              :server-items-length="meta.total"
            >
              <template v-slot:[`body.append`]>
                <tr v-if="activityLogsData.length === 0">
                  <td :colspan="headers.length" class="text-center py-8">
                    <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-history</v-icon>
                    <p class="text-subtitle-1 font-weight-medium mb-1">No activity logs</p>
                    <p class="text-body-2 text-medium-emphasis">Activity logs will appear here as users perform actions</p>
                  </td>
                </tr>
              </template>

              <template v-slot:[`item.user`]="{ item }">
                <span v-if="item.user">
                  {{ item.user.first_name }} {{ item.user.last_name }}
                </span>
                <span v-else class="text-medium-emphasis">System</span>
              </template>

              <template v-slot:[`item.action`]="{ item }">
                <v-chip
                  :color="getActionColor(item.action)"
                  size="small"
                  variant="tonal"
                >
                  {{ item.action }}
                </v-chip>
              </template>

              <template v-slot:[`item.subject_type`]="{ item }">
                <span v-if="item.subject_type" class="text-body-2">
                  {{ formatSubjectType(item.subject_type) }}
                </span>
                <span v-else class="text-medium-emphasis">-</span>
              </template>

              <template v-slot:[`item.created_at`]="{ item }">
                <span class="text-body-2">{{ formatDateTime(item.created_at) }}</span>
              </template>

              <template v-slot:[`item.actions`]="{ item }">
                <v-btn
                  icon="mdi-eye"
                  size="small"
                  variant="text"
                  @click="viewDetails(item)"
                ></v-btn>
              </template>
            </v-data-table>

            <!-- Details Dialog -->
            <v-dialog v-model="detailsDialog" max-width="600">
              <v-card v-if="selectedLog">
                <v-card-title class="d-flex align-center justify-space-between">
                  <span>Activity Log Details</span>
                  <v-btn icon="mdi-close" variant="text" @click="detailsDialog = false"></v-btn>
                </v-card-title>

                <v-divider></v-divider>

                <v-card-text class="pa-4">
                  <v-row>
                    <v-col cols="12" md="6">
                      <div class="text-caption text-medium-emphasis mb-1">User</div>
                      <div class="text-body-1">
                        {{ selectedLog.user ? `${selectedLog.user.first_name} ${selectedLog.user.last_name}` : 'System' }}
                      </div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <div class="text-caption text-medium-emphasis mb-1">Action</div>
                      <v-chip
                        :color="getActionColor(selectedLog.action)"
                        size="small"
                        variant="tonal"
                      >
                        {{ selectedLog.action }}
                      </v-chip>
                    </v-col>
                    <v-col cols="12" md="6">
                      <div class="text-caption text-medium-emphasis mb-1">Subject Type</div>
                      <div class="text-body-1">
                        {{ selectedLog.subject_type ? formatSubjectType(selectedLog.subject_type) : '-' }}
                      </div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <div class="text-caption text-medium-emphasis mb-1">Subject ID</div>
                      <div class="text-body-1">{{ selectedLog.subject_id || '-' }}</div>
                    </v-col>
                    <v-col cols="12">
                      <div class="text-caption text-medium-emphasis mb-1">Description</div>
                      <div class="text-body-1">{{ selectedLog.description || '-' }}</div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <div class="text-caption text-medium-emphasis mb-1">IP Address</div>
                      <div class="text-body-1">{{ selectedLog.ip_address || '-' }}</div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <div class="text-caption text-medium-emphasis mb-1">Timestamp</div>
                      <div class="text-body-1">{{ formatDateTime(selectedLog.created_at) }}</div>
                    </v-col>
                    <v-col cols="12" v-if="selectedLog.changes">
                      <div class="text-caption text-medium-emphasis mb-1">Changes</div>
                      <v-card variant="outlined" class="pa-3">
                        <pre class="text-body-2">{{ JSON.stringify(selectedLog.changes, null, 2) }}</pre>
                      </v-card>
                    </v-col>
                  </v-row>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions>
                  <v-spacer></v-spacer>
                  <v-btn color="primary" @click="detailsDialog = false">Close</v-btn>
                </v-card-actions>
              </v-card>
            </v-dialog>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useActivityLogs } from '@/composables/activity/useActivityLogs'
import type { ActivityLog } from '@/types/activityLog'
import { formatDateTimeForDisplay } from '@/lib/datetime'

const page = ref(1)
const detailsDialog = ref(false)
const selectedLog = ref<ActivityLog | null>(null)

const { data: activityLogsResult, isLoading, error, refetch } = useActivityLogs(page.value)

const activityLogsData = computed(() => activityLogsResult.value?.data || [])
const meta = computed(() => activityLogsResult.value?.meta || {
  total: 0,
  current_page: 1,
  per_page: 15,
  last_page: 1,
})

const headers = [
  { title: 'User', key: 'user', sortable: false },
  { title: 'Action', key: 'action', sortable: true },
  { title: 'Subject Type', key: 'subject_type', sortable: false },
  { title: 'Description', key: 'description', sortable: false },
  { title: 'IP Address', key: 'ip_address', sortable: false },
  { title: 'Timestamp', key: 'created_at', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, width: '100px' },
]

const getActionColor = (action: string) => {
  const actionColors: Record<string, string> = {
    created: 'success',
    updated: 'info',
    deleted: 'error',
    logged_in: 'primary',
    payroll_generated: 'purple',
    payroll_finalized: 'success',
  }
  return actionColors[action] || 'default'
}

const formatSubjectType = (subjectType: string) => {
  // Remove namespace and format
  return subjectType.split('\\').pop() || subjectType
}

const formatDateTime = (dateString: string) => {
  return formatDateTimeForDisplay(dateString)
}

const viewDetails = (log: ActivityLog) => {
  selectedLog.value = log
  detailsDialog.value = true
}
</script>
