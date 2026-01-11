<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <!-- Main Content Card -->
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center justify-space-between flex-wrap w-100">
              <h1 class="text-h6 font-weight-bold mb-0">Payroll Runs</h1>
              <v-btn
                color="primary"
                size="small"
                prepend-icon="mdi-plus"
                @click="openPayrollRunDrawer"
              >
                Generate Payroll
              </v-btn>
            </div>
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-0">
            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
              <p class="mt-3 text-body-2 text-medium-emphasis">Loading payroll runs...</p>
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
                  <div class="font-weight-medium">Failed to load payroll runs</div>
                  <div class="text-caption">{{ error.message }}</div>
                </div>
                <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
              </div>
            </v-alert>

            <!-- Payroll Runs Table -->
            <v-data-table
              v-else
              :items="payrollRuns"
              :headers="headers"
              :loading="isLoading"
              density="compact"
              item-key="uuid"
              class="payroll-runs-table"
              :items-per-page="15"
              :items-per-page-options="[10, 15, 25, 50]"
              :hide-no-data="true"
              :no-data-text="''"
              :row-height="40"
            >
              <template v-slot:[`body.append`]>
                <tr v-if="payrollRuns.length === 0">
                  <td :colspan="headers.length" class="text-center py-8">
                    <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-cash-multiple</v-icon>
                    <p class="text-subtitle-1 font-weight-medium mb-1">No payroll runs yet</p>
                    <p class="text-body-2 text-medium-emphasis mb-3">Get started by creating your first payroll run</p>
                    <v-btn
                      color="primary"
                      size="small"
                      prepend-icon="mdi-plus"
                      @click="openPayrollRunDrawer"
                    >
                      Generate Payroll
                    </v-btn>
                  </td>
                </tr>
              </template>

              <template v-slot:[`item.company`]="{ item }">
                <span class="text-body-2">{{ item.company?.name || '-' }}</span>
              </template>

              <template v-slot:[`item.period`]="{ item }">
                <span class="text-body-2">{{ formatDate(item.period_start) }} - {{ formatDate(item.period_end) }}</span>
              </template>

              <template v-slot:[`item.pay_date`]="{ item }">
                <span class="text-body-2">{{ formatDate(item.pay_date) }}</span>
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
                      v-if="item.status === 'draft'"
                      prepend-icon="mdi-play"
                      title="Generate"
                      @click="generatePayroll(item)"
                      :disabled="generateMutation.isPending.value"
                    ></v-list-item>
                    <v-list-item
                      v-if="item.status !== 'draft'"
                      prepend-icon="mdi-eye"
                      title="View Payrolls"
                      @click="viewPayrolls(item)"
                    ></v-list-item>
                    <v-list-item
                      v-if="item.status !== 'draft'"
                      prepend-icon="mdi-file-excel"
                      title="Export Excel"
                      @click="exportPayroll(item)"
                      :disabled="exportingPayrollRunUuid === item.uuid"
                    ></v-list-item>
                  </v-list>
                </v-menu>
              </template>
            </v-data-table>

            <!-- Pagination -->
            <v-card-actions v-if="payrollRunsData?.meta" class="px-4 py-3">
              <div class="text-body-2 text-medium-emphasis">
                Showing {{ payrollRunsData.meta.from }} to {{ payrollRunsData.meta.to }} of {{ payrollRunsData.meta.total }} results
              </div>
              <v-spacer></v-spacer>
              <v-pagination
                v-model="currentPage"
                :length="payrollRunsData.meta.last_page"
                :total-visible="7"
                @update:model-value="handlePageChange"
              ></v-pagination>
            </v-card-actions>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Payroll Run Form Drawer -->
    <PayrollRunForm
      v-model="showPayrollRunDrawer"
      @success="handlePayrollRunCreated"
    />

    <!-- Employee Payroll Table Drawer -->
    <EmployeePayrollTable
      v-model="showEmployeePayrollTable"
      :payroll-run-uuid="selectedPayrollRunUuid"
      @close="showEmployeePayrollTable = false"
    />
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePayrollRuns, useGeneratePayroll, useExportPayrollRun } from '@/composables/usePayroll'
import type { PayrollRun } from '@/types/payroll'
import PayrollRunForm from './PayrollRunForm.vue'
import EmployeePayrollTable from './EmployeePayrollTable.vue'
import { useNotification } from '@/composables/useNotification'

const notification = useNotification()

// Payroll runs query
const currentPage = ref(1)
const { data: payrollRunsData, isLoading, error, refetch } = usePayrollRuns(currentPage.value, true)
const generateMutation = useGeneratePayroll()
const exportMutation = useExportPayrollRun()

// Drawer state
const showPayrollRunDrawer = ref(false)
const showEmployeePayrollTable = ref(false)
const selectedPayrollRunUuid = ref<string | null>(null)

// Export loading state per payroll run
const exportingPayrollRunUuid = ref<string | null>(null)

// Computed
const payrollRuns = computed(() => payrollRunsData.value?.data || [])

const headers = [
  { title: 'Company', key: 'company', sortable: true },
  { title: 'Period', key: 'period', sortable: false },
  { title: 'Pay Date', key: 'pay_date', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
]

// Methods
const openPayrollRunDrawer = () => {
  showPayrollRunDrawer.value = true
}

const handlePayrollRunCreated = () => {
  showPayrollRunDrawer.value = false
  refetch()
  notification.showSuccess('Payroll run created successfully')
}

const handlePageChange = (page: number) => {
  currentPage.value = page
  refetch()
}

const formatDate = (date: string | null | undefined): string => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString()
}

const getStatusColor = (status: string): string => {
  switch (status) {
    case 'draft':
      return 'grey'
    case 'processed':
      return 'info'
    case 'paid':
      return 'success'
    default:
      return 'grey'
  }
}

const generatePayroll = async (payrollRun: PayrollRun) => {
  try {
    await generateMutation.mutateAsync(payrollRun.uuid)
    notification.showSuccess('Payroll generated successfully')
    refetch()
  } catch (error: unknown) {
    const err = error as { message?: string }
    notification.showError(err?.message || 'Failed to generate payroll')
  }
}

const viewPayrolls = (payrollRun: PayrollRun) => {
  selectedPayrollRunUuid.value = payrollRun.uuid
  showEmployeePayrollTable.value = true
}

const exportPayroll = async (payrollRun: PayrollRun) => {
  exportingPayrollRunUuid.value = payrollRun.uuid
  try {
    await exportMutation.mutateAsync({
      payrollRunUuid: payrollRun.uuid,
      periodStart: payrollRun.period_start,
      periodEnd: payrollRun.period_end,
    })
    notification.showSuccess('Payroll exported successfully!')
  } catch (err: unknown) {
    const error = err as { message?: string; response?: { data?: { message?: string } } }
    const message = error?.message || error?.response?.data?.message || 'Failed to export payroll.'
    notification.showError(message)
  } finally {
    exportingPayrollRunUuid.value = null
  }
}
</script>

<style scoped>
.payroll-runs-table :deep(.v-data-table__thead th) {
  background-color: rgba(0, 0, 0, 0.02);
  font-weight: 600;
  font-size: 0.875rem;
  padding: 8px 16px;
}

.payroll-runs-table :deep(.v-data-table__tbody td) {
  padding: 8px 16px;
  font-size: 0.875rem;
}

.action-btn {
  transition: transform 0.2s;
}

.action-btn:hover {
  transform: scale(1.1);
}
</style>
