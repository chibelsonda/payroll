<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <h1 class="text-h6 font-weight-bold mb-0">Reports Dashboard</h1>
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-4">
            <!-- Date Range Selector -->
            <v-row class="mb-4">
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="startDate"
                  type="date"
                  label="Start Date"
                  density="compact"
                  variant="outlined"
                  hide-details
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="endDate"
                  type="date"
                  label="End Date"
                  density="compact"
                  variant="outlined"
                  hide-details
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="4" class="d-flex align-center">
                <v-btn
                  color="primary"
                  prepend-icon="mdi-filter"
                  @click="loadReports"
                  :disabled="!startDate || !endDate"
                >
                  Generate Reports
                </v-btn>
              </v-col>
            </v-row>

            <!-- Tabs for different reports -->
            <v-tabs v-model="activeTab" color="primary" class="mb-4">
              <v-tab value="summary">Payroll Summary</v-tab>
              <v-tab value="tax">Tax Report</v-tab>
              <v-tab value="contributions">Contributions Report</v-tab>
            </v-tabs>

            <v-window v-model="activeTab">
              <!-- Payroll Summary Tab -->
              <v-window-item value="summary">
                <div v-if="summaryLoading" class="text-center py-8">
                  <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
                  <p class="mt-3 text-body-2 text-medium-emphasis">Loading payroll summary...</p>
                </div>

                <v-alert
                  v-else-if="summaryError"
                  type="error"
                  variant="tonal"
                  class="mb-4"
                  rounded="lg"
                >
                  Failed to load payroll summary: {{ summaryError.message }}
                </v-alert>

                <div v-else-if="summaryData" class="report-summary">
                  <v-row>
                    <v-col cols="12" md="4">
                      <v-card variant="outlined" class="pa-4">
                        <div class="text-caption text-medium-emphasis mb-1">Total Payroll Runs</div>
                        <div class="text-h5 font-weight-bold">{{ summaryData.total_payroll_runs }}</div>
                      </v-card>
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-card variant="outlined" class="pa-4">
                        <div class="text-caption text-medium-emphasis mb-1">Total Employees Paid</div>
                        <div class="text-h5 font-weight-bold">{{ summaryData.total_employees_paid }}</div>
                      </v-card>
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-card variant="outlined" class="pa-4">
                        <div class="text-caption text-medium-emphasis mb-1">Total Gross Pay</div>
                        <div class="text-h5 font-weight-bold">₱{{ formatCurrency(summaryData.total_gross_pay) }}</div>
                      </v-card>
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-card variant="outlined" class="pa-4">
                        <div class="text-caption text-medium-emphasis mb-1">Total Net Pay</div>
                        <div class="text-h5 font-weight-bold">₱{{ formatCurrency(summaryData.total_net_pay) }}</div>
                      </v-card>
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-card variant="outlined" class="pa-4">
                        <div class="text-caption text-medium-emphasis mb-1">Total Deductions</div>
                        <div class="text-h5 font-weight-bold">₱{{ formatCurrency(summaryData.total_deductions) }}</div>
                      </v-card>
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-card variant="outlined" class="pa-4">
                        <div class="text-caption text-medium-emphasis mb-1">Total Tax</div>
                        <div class="text-h5 font-weight-bold">₱{{ formatCurrency(summaryData.total_tax) }}</div>
                      </v-card>
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-card variant="outlined" class="pa-4">
                        <div class="text-caption text-medium-emphasis mb-1">SSS Contributions</div>
                        <div class="text-h5 font-weight-bold">₱{{ formatCurrency(summaryData.total_sss_contribution) }}</div>
                      </v-card>
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-card variant="outlined" class="pa-4">
                        <div class="text-caption text-medium-emphasis mb-1">PhilHealth Contributions</div>
                        <div class="text-h5 font-weight-bold">₱{{ formatCurrency(summaryData.total_philhealth_contribution) }}</div>
                      </v-card>
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-card variant="outlined" class="pa-4">
                        <div class="text-caption text-medium-emphasis mb-1">Pag-IBIG Contributions</div>
                        <div class="text-h5 font-weight-bold">₱{{ formatCurrency(summaryData.total_pagibig_contribution) }}</div>
                      </v-card>
                    </v-col>
                  </v-row>
                </div>
              </v-window-item>

              <!-- Tax Report Tab -->
              <v-window-item value="tax">
                <div v-if="taxLoading" class="text-center py-8">
                  <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
                  <p class="mt-3 text-body-2 text-medium-emphasis">Loading tax report...</p>
                </div>

                <v-alert
                  v-else-if="taxError"
                  type="error"
                  variant="tonal"
                  class="mb-4"
                  rounded="lg"
                >
                  Failed to load tax report: {{ taxError.message }}
                </v-alert>

                <v-data-table
                  v-else
                  :items="taxData || []"
                  :headers="taxHeaders"
                  density="compact"
                  :hide-no-data="true"
                  class="mt-4"
                >
                  <template v-slot:[`item.tax_amount`]="{ item }">
                    ₱{{ formatCurrency(item.tax_amount) }}
                  </template>
                  <template v-slot:[`item.gross_pay`]="{ item }">
                    ₱{{ formatCurrency(item.gross_pay) }}
                  </template>
                  <template v-slot:[`item.net_pay`]="{ item }">
                    ₱{{ formatCurrency(item.net_pay) }}
                  </template>
                </v-data-table>
              </v-window-item>

              <!-- Contributions Report Tab -->
              <v-window-item value="contributions">
                <div v-if="contributionsLoading" class="text-center py-8">
                  <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
                  <p class="mt-3 text-body-2 text-medium-emphasis">Loading contributions report...</p>
                </div>

                <v-alert
                  v-else-if="contributionsError"
                  type="error"
                  variant="tonal"
                  class="mb-4"
                  rounded="lg"
                >
                  Failed to load contributions report: {{ contributionsError.message }}
                </v-alert>

                <v-data-table
                  v-else
                  :items="contributionsData || []"
                  :headers="contributionsHeaders"
                  density="compact"
                  :hide-no-data="true"
                  class="mt-4"
                >
                  <template v-slot:[`item.sss_contribution`]="{ item }">
                    ₱{{ formatCurrency(item.sss_contribution) }}
                  </template>
                  <template v-slot:[`item.philhealth_contribution`]="{ item }">
                    ₱{{ formatCurrency(item.philhealth_contribution) }}
                  </template>
                  <template v-slot:[`item.pagibig_contribution`]="{ item }">
                    ₱{{ formatCurrency(item.pagibig_contribution) }}
                  </template>
                  <template v-slot:[`item.total_contributions`]="{ item }">
                    ₱{{ formatCurrency(item.total_contributions) }}
                  </template>
                </v-data-table>
              </v-window-item>
            </v-window>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { ref, computed } from 'vue'
import { usePayrollSummaryReport, useTaxReport, useContributionReport } from '@/composables/report/useReports'
import type { PayrollSummaryReport, TaxReportEntry, ContributionReportEntry } from '@/types/report'

const startDate = ref('')
const endDate = ref('')
const activeTab = ref('summary')

// Set default date range (current month)
const today = new Date()
const firstDay = new Date(today.getFullYear(), today.getMonth(), 1)
const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0)

startDate.value = firstDay.toISOString().split('T')[0]
endDate.value = lastDay.toISOString().split('T')[0]

const enabled = computed(() => !!startDate.value && !!endDate.value)

// Payroll Summary
const {
  data: summaryData,
  isLoading: summaryLoading,
  error: summaryError,
  refetch: refetchSummary,
} = usePayrollSummaryReport(startDate, endDate, enabled.value)

// Tax Report
const {
  data: taxData,
  isLoading: taxLoading,
  error: taxError,
  refetch: refetchTax,
} = useTaxReport(startDate, endDate, enabled.value)

// Contributions Report
const {
  data: contributionsData,
  isLoading: contributionsLoading,
  error: contributionsError,
  refetch: refetchContributions,
} = useContributionReport(startDate, endDate, enabled.value)

const loadReports = () => {
  if (startDate.value && endDate.value) {
    refetchSummary()
    refetchTax()
    refetchContributions()
  }
}

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount)
}

const taxHeaders = [
  { title: 'Employee Name', key: 'employee_name' },
  { title: 'Employee No.', key: 'employee_no' },
  { title: 'Period', key: 'payroll_run_period' },
  { title: 'Tax Amount', key: 'tax_amount' },
  { title: 'Gross Pay', key: 'gross_pay' },
  { title: 'Net Pay', key: 'net_pay' },
]

const contributionsHeaders = [
  { title: 'Employee Name', key: 'employee_name' },
  { title: 'Employee No.', key: 'employee_no' },
  { title: 'Period', key: 'payroll_run_period' },
  { title: 'SSS', key: 'sss_contribution' },
  { title: 'PhilHealth', key: 'philhealth_contribution' },
  { title: 'Pag-IBIG', key: 'pagibig_contribution' },
  { title: 'Total Contributions', key: 'total_contributions' },
]
</script>
