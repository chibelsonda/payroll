<template>
  <v-navigation-drawer
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    location="right"
    temporary
    width="1000"
    class="drawer employee-payroll-drawer"
  >
    <v-card class="h-100 d-flex flex-column" flat>
      <!-- Enhanced Header -->
      <v-card-title class="employee-drawer-header flex-shrink-0 py-3 px-5">
        <div class="d-flex align-center w-100">
          <v-avatar
            color="primary"
            size="40"
            class="me-3"
          >
            <v-icon color="white">mdi-cash-multiple</v-icon>
          </v-avatar>
          <div class="flex-grow-1">
            <div class="text-h6 font-weight-bold">Employee Payrolls</div>
            <div class="text-caption text-medium-emphasis mt-1" v-if="payrollRun">
              {{ payrollRun.company?.name }} • {{ formatDateRange(payrollRun.period_start, payrollRun.period_end) }}
            </div>
            <div class="text-caption text-medium-emphasis mt-1" v-else>
              View and manage employee payroll details
            </div>
          </div>
          <v-btn
            icon="mdi-close"
            variant="text"
            size="small"
            @click="$emit('update:modelValue', false)"
            class="ml-2"
          ></v-btn>
        </div>
      </v-card-title>

      <v-divider class="flex-shrink-0"></v-divider>

      <v-card-text class="flex-grow-1 overflow-y-auto pa-4" style="min-height: 0;">
        <!-- Loading State -->
        <div v-if="isLoading" class="text-center py-12">
          <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
          <p class="mt-4 text-body-2 text-medium-emphasis">Loading payrolls...</p>
        </div>

        <!-- Error State -->
        <v-alert
          v-else-if="error"
          type="error"
          variant="tonal"
          class="mb-4"
          rounded="lg"
          density="compact"
        >
          <div class="d-flex align-center">
            <v-icon class="me-3">mdi-alert-circle</v-icon>
            <div class="flex-grow-1">
              <div class="font-weight-medium">Failed to load payrolls</div>
              <div class="text-caption">{{ error.message }}</div>
            </div>
            <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
          </div>
        </v-alert>

        <!-- Payrolls Table -->
        <v-data-table
          v-else
          :items="payrolls"
          :headers="headers"
          :loading="isLoading"
          density="compact"
          item-key="uuid"
          class="payrolls-table"
          :items-per-page="15"
          :items-per-page-options="[10, 15, 25, 50]"
          :hide-no-data="true"
          :no-data-text="''"
          :row-height="40"
          elevation="0"
        >
          <template v-slot:[`body.append`]>
            <tr v-if="payrolls.length === 0">
              <td :colspan="headers.length" class="text-center py-8">
                <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-cash-off</v-icon>
                <p class="text-subtitle-1 font-weight-medium mb-1">No payrolls found</p>
                <p class="text-body-2 text-medium-emphasis">Payroll has not been generated yet</p>
              </td>
            </tr>
          </template>

          <template v-slot:[`item.employee`]="{ item }">
            <div class="d-flex align-center">
              <v-avatar size="32" color="primary" class="me-3">
                <span class="text-white text-caption font-weight-bold">
                  {{ getInitials(item.employee?.user) }}
                </span>
              </v-avatar>
              <div>
                <div class="font-weight-medium text-body-2">
                  {{ item.employee?.user?.first_name }} {{ item.employee?.user?.last_name }}
                </div>
                <div class="text-caption text-medium-emphasis">{{ item.employee?.employee_no }}</div>
              </div>
            </div>
          </template>

          <template v-slot:[`item.basic_salary`]="{ item }">
            <span class="text-body-2">{{ formatCurrency(item.basic_salary) }}</span>
          </template>

          <template v-slot:[`item.gross_pay`]="{ item }">
            <span class="text-body-2 font-weight-medium">{{ formatCurrency(item.gross_pay) }}</span>
          </template>

          <template v-slot:[`item.total_deductions`]="{ item }">
            <span class="text-body-2 text-error">{{ formatCurrency(item.total_deductions) }}</span>
          </template>

          <template v-slot:[`item.net_pay`]="{ item }">
            <span class="text-body-2 font-weight-bold text-primary">{{ formatCurrency(item.net_pay) }}</span>
          </template>

          <template v-slot:[`item.actions`]="{ item }">
            <v-btn
              color="primary"
              size="small"
              variant="text"
              prepend-icon="mdi-eye"
              @click="viewPayrollDetails(item)"
            >
              Details
            </v-btn>
          </template>
        </v-data-table>
      </v-card-text>

      <!-- Summary Stats Footer -->
      <v-divider class="flex-shrink-0" v-if="payrolls.length > 0"></v-divider>
      <v-card-text class="flex-shrink-0 pa-5 bg-grey-lighten-5" v-if="payrolls.length > 0">
        <div class="d-flex align-center justify-space-between flex-wrap gap-4">
          <div class="d-flex align-center">
            <v-icon size="20" color="primary" class="me-2">mdi-account-group</v-icon>
            <div>
              <div class="text-caption text-medium-emphasis">Total Employees</div>
              <div class="text-body-1 font-weight-bold">{{ payrolls.length }}</div>
            </div>
          </div>
          <v-divider vertical class="mx-2" style="height: 32px;"></v-divider>
          <div class="d-flex align-center">
            <v-icon size="20" color="success" class="me-2">mdi-cash-check</v-icon>
            <div>
              <div class="text-caption text-medium-emphasis">Total Gross Pay</div>
              <div class="text-body-1 font-weight-bold text-success">{{ formatCurrency(totalGrossPay) }}</div>
            </div>
          </div>
          <v-divider vertical class="mx-2" style="height: 32px;"></v-divider>
          <div class="d-flex align-center">
            <v-icon size="20" color="error" class="me-2">mdi-minus-circle</v-icon>
            <div>
              <div class="text-caption text-medium-emphasis">Total Deductions</div>
              <div class="text-body-1 font-weight-bold text-error">{{ formatCurrency(totalDeductions) }}</div>
            </div>
          </div>
          <v-divider vertical class="mx-2" style="height: 32px;"></v-divider>
          <div class="d-flex align-center">
            <v-icon size="20" color="primary" class="me-2">mdi-wallet</v-icon>
            <div>
              <div class="text-caption text-medium-emphasis">Total Net Pay</div>
              <div class="text-body-1 font-weight-bold text-primary">{{ formatCurrency(totalNetPay) }}</div>
            </div>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <!-- Employee Payroll Details Drawer -->
    <EmployeePayrollDrawer
      v-model="showPayrollDrawer"
      :payroll-uuid="selectedPayrollUuid"
      @close="showPayrollDrawer = false"
    />
  </v-navigation-drawer>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePayrolls, usePayrollRun } from '@/composables/usePayroll'
import EmployeePayrollDrawer from './EmployeePayrollDrawer.vue'
import type { Payroll, PayrollRun } from '@/types/payroll'

interface Props {
  modelValue: boolean
  payrollRunUuid: string | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'close': []
}>()

const showPayrollDrawer = ref(false)
const selectedPayrollUuid = ref<string | null>(null)

const { data: payrollsData, isLoading, error, refetch } = usePayrolls(
  computed(() => props.payrollRunUuid)
)

const { data: payrollRunData } = usePayrollRun(
  computed(() => props.payrollRunUuid)
)

const payrolls = computed(() => (payrollsData.value as Payroll[] | undefined) || [])
const payrollRun = computed(() => payrollRunData.value as PayrollRun | undefined)

const headers = [
  { title: 'Employee', key: 'employee', sortable: true },
  { title: 'Basic Salary', key: 'basic_salary', sortable: true, align: 'end' as const },
  { title: 'Gross Pay', key: 'gross_pay', sortable: true, align: 'end' as const },
  { title: 'Deductions', key: 'total_deductions', sortable: true, align: 'end' as const },
  { title: 'Net Pay', key: 'net_pay', sortable: true, align: 'end' as const },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
]

// Computed totals
const totalGrossPay = computed(() => {
  return payrolls.value.reduce((sum, payroll) => {
    return sum + parseFloat(payroll.gross_pay.toString())
  }, 0)
})

const totalDeductions = computed(() => {
  return payrolls.value.reduce((sum, payroll) => {
    return sum + parseFloat(payroll.total_deductions.toString())
  }, 0)
})

const totalNetPay = computed(() => {
  return payrolls.value.reduce((sum, payroll) => {
    return sum + parseFloat(payroll.net_pay.toString())
  }, 0)
})

const formatCurrency = (value: string | number): string => {
  const num = typeof value === 'string' ? parseFloat(value) : value
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(num)
}

const formatDateRange = (start: string, end: string): string => {
  const startDate = new Date(start).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
  const endDate = new Date(end).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
  return `${startDate} - ${endDate}`
}

const getInitials = (user?: { first_name?: string; last_name?: string }): string => {
  if (!user) return '??'
  const first = user.first_name?.charAt(0).toUpperCase() || ''
  const last = user.last_name?.charAt(0).toUpperCase() || ''
  return `${first}${last}` || '??'
}

const viewPayrollDetails = (payroll: Payroll) => {
  selectedPayrollUuid.value = payroll.uuid
  showPayrollDrawer.value = true
}
</script>

<style scoped>
.payrolls-table {
  border-radius: 8px;
  overflow: hidden;
}

.payrolls-table :deep(.v-data-table) {
  border-radius: 8px;
  overflow: hidden;
}

.payrolls-table :deep(.v-data-table__thead th) {
  background-color: rgba(0, 0, 0, 0.03);
  font-weight: 600;
  font-size: 0.875rem;
  padding: 14px 16px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: rgba(0, 0, 0, 0.87);
}

.payrolls-table :deep(.v-data-table__tbody td) {
  padding: 14px 16px;
  font-size: 0.875rem;
}

.payrolls-table :deep(.v-data-table__tbody tr:hover) {
  background-color: rgba(25, 118, 210, 0.04);
}

.payrolls-table :deep(.v-data-table__tbody tr) {
  transition: background-color 0.2s ease;
}

.payrolls-table :deep(.v-data-table__wrapper) {
  border-radius: 8px;
}
</style>
