<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center justify-space-between flex-wrap w-100">
              <h1 class="text-h6 font-weight-bold mb-0">Loans Management</h1>
              <v-btn
                color="primary"
                size="small"
                prepend-icon="mdi-plus"
                @click="showLoanForm = true"
              >
                Add Loan
              </v-btn>
            </div>
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-0">
            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
              <p class="mt-3 text-body-2 text-medium-emphasis">Loading loans...</p>
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
                  <div class="font-weight-medium">Failed to load loans</div>
                  <div class="text-caption">{{ error.message }}</div>
                </div>
                <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
              </div>
            </v-alert>

            <!-- Loans Table -->
            <v-data-table
              v-else
              :items="loans"
              :headers="headers"
              :loading="isLoading"
              density="compact"
              item-key="uuid"
              class="loans-table"
              :items-per-page="15"
              :items-per-page-options="[10, 15, 25, 50]"
              :hide-no-data="true"
              :no-data-text="''"
              :row-height="40"
            >
              <template v-slot:[`body.append`]>
                <tr v-if="loans.length === 0">
                  <td :colspan="headers.length" class="text-center py-8">
                    <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-cash-multiple</v-icon>
                    <p class="text-subtitle-1 font-weight-medium mb-1">No loans</p>
                    <p class="text-body-2 text-medium-emphasis mb-3">Start by adding a new loan</p>
                    <v-btn
                      color="primary"
                      size="small"
                      prepend-icon="mdi-plus"
                      @click="showLoanForm = true"
                    >
                      Add Loan
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

              <template v-slot:[`item.amount`]="{ item }">
                {{ formatCurrency(item.amount) }}
              </template>

              <template v-slot:[`item.balance`]="{ item }">
                <span :class="parseFloat(item.balance) > 0 ? 'text-error' : 'text-success'">
                  {{ formatCurrency(item.balance) }}
                </span>
              </template>

              <template v-slot:[`item.start_date`]="{ item }">
                {{ formatDate(item.start_date) }}
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
                      prepend-icon="mdi-eye"
                      title="View Details"
                      @click="viewLoanDetails(item)"
                    ></v-list-item>
                    <v-list-item
                      prepend-icon="mdi-history"
                      title="Payment History"
                      @click="viewPaymentHistory(item)"
                    ></v-list-item>
                  </v-list>
                </v-menu>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Loan Form Drawer (to be implemented) -->
    <!-- <LoanForm
      v-model="showLoanForm"
      @success="handleSuccess"
      @close="handleClose"
    /> -->
  </v-container>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { Loan } from '@/types/loan'

// TODO: Replace with actual composable when backend API is ready
const isLoading = ref(false)
const error = ref<Error | null>(null)
const loans = ref<Loan[]>([])

const showLoanForm = ref(false)

const headers = [
  { title: 'Employee', key: 'employee', sortable: true },
  { title: 'Amount', key: 'amount', sortable: true, align: 'end' as const },
  { title: 'Balance', key: 'balance', sortable: true, align: 'end' as const },
  { title: 'Start Date', key: 'start_date', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
]

const formatCurrency = (value: string | number): string => {
  const num = typeof value === 'string' ? parseFloat(value) : value
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(num)
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const getInitials = (user?: { first_name?: string; last_name?: string }): string => {
  if (!user) return '??'
  const first = user.first_name?.charAt(0).toUpperCase() || ''
  const last = user.last_name?.charAt(0).toUpperCase() || ''
  return `${first}${last}` || '??'
}

const viewLoanDetails = (loan: Loan) => {
  // TODO: Implement view details functionality
  console.log('View loan details:', loan)
}

const viewPaymentHistory = (loan: Loan) => {
  // TODO: Implement payment history functionality
  console.log('View payment history:', loan)
}

const refetch = () => {
  // TODO: Implement refetch functionality
  console.log('Refetch loans')
}

const handleSuccess = () => {
  showLoanForm.value = false
  refetch()
}

const handleClose = () => {
  showLoanForm.value = false
}
</script>

<style scoped>
.loans-table {
  border-radius: 8px;
  overflow: hidden;
}

.loans-table :deep(.v-data-table__thead th) {
  background-color: rgba(0, 0, 0, 0.03);
  font-weight: 600;
  font-size: 0.875rem;
  padding: 14px 16px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: rgba(0, 0, 0, 0.87);
}

.loans-table :deep(.v-data-table__tbody td) {
  padding: 14px 16px;
  font-size: 0.875rem;
}

.loans-table :deep(.v-data-table__tbody tr:hover) {
  background-color: rgba(25, 118, 210, 0.04);
}

.action-btn {
  opacity: 0.7;
}

.action-btn:hover {
  opacity: 1;
}
</style>
