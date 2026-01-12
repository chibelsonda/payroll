<template>
  <v-navigation-drawer
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    location="right"
    temporary
    width="600"
      class="drawer employee-payroll-details-drawer"
  >
    <v-card class="h-100 d-flex flex-column" flat>
      <!-- Header -->
      <v-card-title class="employee-drawer-header px-4 py-3 d-flex align-center justify-space-between">
        <span class="text-h6 font-weight-bold">Payroll Details</span>
        <v-btn
          icon="mdi-close"
          variant="text"
          size="small"
          @click="$emit('update:modelValue', false)"
        ></v-btn>
      </v-card-title>

      <v-divider class="flex-shrink-0"></v-divider>

      <v-card-text class="flex-grow-1 overflow-y-auto pa-6" style="min-height: 0;">
        <!-- Loading State -->
        <div v-if="isLoading" class="text-center py-8">
          <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
          <p class="mt-3 text-body-2 text-medium-emphasis">Loading payroll details...</p>
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
              <div class="font-weight-medium">Failed to load payroll details</div>
              <div class="text-caption">{{ error.message }}</div>
            </div>
            <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
          </div>
        </v-alert>

        <!-- Payroll Details -->
        <div v-else-if="payroll">
          <!-- Employee Info -->
          <div class="mb-6">
            <div class="text-subtitle-2 font-weight-medium mb-3 text-primary">Employee Information</div>
            <v-card variant="outlined" class="pa-4">
              <div class="d-flex align-center mb-3">
                <v-avatar color="primary" size="48" class="me-3">
                  <span class="text-white text-body-1 font-weight-bold">
                    {{ getInitials(payroll.employee?.user) }}
                  </span>
                </v-avatar>
                <div>
                  <div class="text-h6 font-weight-medium">
                    {{ payroll.employee?.user?.first_name }} {{ payroll.employee?.user?.last_name }}
                  </div>
                  <div class="text-body-2 text-medium-emphasis">{{ payroll.employee?.employee_no }}</div>
                </div>
              </div>
            </v-card>
          </div>

          <!-- Earnings -->
          <div class="mb-6">
            <div class="text-subtitle-2 font-weight-medium mb-3 text-primary">Earnings</div>
            <v-card variant="outlined">
              <v-table density="compact">
                <thead>
                  <tr>
                    <th class="text-left">Type</th>
                    <th class="text-left">Description</th>
                    <th class="text-right">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="earning in payroll.earnings" :key="earning.uuid">
                    <td>{{ earning.type }}</td>
                    <td class="text-medium-emphasis">{{ earning.description || '-' }}</td>
                    <td class="text-right font-weight-medium">{{ formatCurrency(earning.amount) }}</td>
                  </tr>
                  <tr v-if="!payroll.earnings || payroll.earnings.length === 0">
                    <td colspan="3" class="text-center text-medium-emphasis py-4">No earnings</td>
                  </tr>
                  <tr class="font-weight-bold">
                    <td colspan="2" class="text-right">Basic Salary:</td>
                    <td class="text-right">{{ formatCurrency(payroll.basic_salary) }}</td>
                  </tr>
                  <tr class="font-weight-bold">
                    <td colspan="2" class="text-right">Total Gross Pay:</td>
                    <td class="text-right text-primary">{{ formatCurrency(payroll.gross_pay) }}</td>
                  </tr>
                </tbody>
              </v-table>
            </v-card>
          </div>

          <!-- Deductions -->
          <div class="mb-6">
            <div class="text-subtitle-2 font-weight-medium mb-3 text-primary">Deductions</div>
            <v-card variant="outlined">
              <v-table density="compact">
                <thead>
                  <tr>
                    <th class="text-left">Type</th>
                    <th class="text-left">Description</th>
                    <th class="text-right">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="deduction in payroll.deductions" :key="deduction.uuid">
                    <td>{{ deduction.type }}</td>
                    <td class="text-medium-emphasis">{{ deduction.description || '-' }}</td>
                    <td class="text-right font-weight-medium text-error">{{ formatCurrency(deduction.amount) }}</td>
                  </tr>
                  <tr v-if="!payroll.deductions || payroll.deductions.length === 0">
                    <td colspan="3" class="text-center text-medium-emphasis py-4">No deductions</td>
                  </tr>
                  <tr class="font-weight-bold">
                    <td colspan="2" class="text-right">Total Deductions:</td>
                    <td class="text-right text-error">{{ formatCurrency(payroll.total_deductions) }}</td>
                  </tr>
                </tbody>
              </v-table>
            </v-card>
          </div>

          <!-- Summary -->
          <div class="mb-4">
            <v-card color="primary" variant="flat" class="pa-4">
              <div class="d-flex justify-space-between align-center">
                <span class="text-h6 font-weight-bold text-white">Net Pay</span>
                <span class="text-h5 font-weight-bold text-white">{{ formatCurrency(payroll.net_pay) }}</span>
              </div>
            </v-card>
          </div>
        </div>
      </v-card-text>
    </v-card>
  </v-navigation-drawer>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePayroll } from '@/composables'
import type { Payroll } from '@/types/payroll'

interface Props {
  modelValue: boolean
  payrollUuid: string | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'close': []
}>()

const { data: payrollData, isLoading, error, refetch } = usePayroll(
  computed(() => props.payrollUuid)
)

const payroll = computed(() => payrollData.value as Payroll | undefined)

const formatCurrency = (value: string | number): string => {
  const num = typeof value === 'string' ? parseFloat(value) : value
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(num)
}

const getInitials = (user?: { first_name?: string; last_name?: string }): string => {
  if (!user) return '??'
  const first = user.first_name?.charAt(0).toUpperCase() || ''
  const last = user.last_name?.charAt(0).toUpperCase() || ''
  return `${first}${last}` || '??'
}
</script>

<style scoped>
/* Employee info card border */
.v-card[class*="variant-outlined"] {
  border-color: rgba(0, 0, 0, 0.12) !important;
}

/* Earnings and Deductions table card borders */
.payroll-details-card :deep(.v-card[class*="variant-outlined"]) {
  border-color: rgba(0, 0, 0, 0.12) !important;
}

/* Table borders inside cards */
.payroll-details-card :deep(.v-table) {
  border-top: 1px solid rgba(0, 0, 0, 0.12);
  border-bottom: 1px solid rgba(0, 0, 0, 0.12);
}

.payroll-details-card :deep(.v-table thead) {
  border-bottom: 1px solid rgba(0, 0, 0, 0.12);
}

.payroll-details-card :deep(.v-table tbody tr:not(:last-child)) {
  border-bottom: 1px solid rgba(0, 0, 0, 0.12);
}
</style>
