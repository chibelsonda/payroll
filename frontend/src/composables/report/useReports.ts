import { useQuery } from '@tanstack/vue-query'
import type { Ref } from 'vue'
import apiAxios from '@/lib/axios'
import type { PayrollSummaryReport, TaxReportEntry, ContributionReportEntry, EmployeeLedgerEntry } from '@/types/report'

export function usePayrollSummaryReport(startDate: Ref<string> | string, endDate: Ref<string> | string, enabled = true) {
  const startDateValue = typeof startDate === 'string' ? startDate : startDate.value
  const endDateValue = typeof endDate === 'string' ? endDate : endDate.value
  return useQuery({
    queryKey: ['reports', 'payroll-summary', startDateValue, endDateValue],
    queryFn: async () => {
      const response = await apiAxios.get<{ data: PayrollSummaryReport }>('/reports/payroll-summary', {
        params: { start_date: startDateValue, end_date: endDateValue },
      })
      return response.data.data
    },
    enabled: enabled && !!startDateValue && !!endDateValue,
  })
}

export function useTaxReport(startDate: string, endDate: string, enabled = true) {
  return useQuery({
    queryKey: ['reports', 'tax', startDate, endDate],
    queryFn: async () => {
      const response = await apiAxios.get<{ data: TaxReportEntry[] }>('/reports/tax', {
        params: { start_date: startDate, end_date: endDate },
      })
      return response.data.data
    },
    enabled: enabled && !!startDate && !!endDate,
  })
}

export function useContributionReport(startDate: Ref<string> | string, endDate: Ref<string> | string, enabled = true) {
  const startDateValue = typeof startDate === 'string' ? startDate : startDate.value
  const endDateValue = typeof endDate === 'string' ? endDate : endDate.value
  return useQuery({
    queryKey: ['reports', 'contributions', startDateValue, endDateValue],
    queryFn: async () => {
      const response = await apiAxios.get<{ data: ContributionReportEntry[] }>('/reports/contribution-report', {
        params: { start_date: startDateValue, end_date: endDateValue },
      })
      return response.data.data
    },
    enabled: enabled && !!startDateValue && !!endDateValue,
  })
}

export function useEmployeeLedger(employeeUuid: string, startDate: string, endDate: string, enabled = true) {
  return useQuery({
    queryKey: ['reports', 'employee-ledger', employeeUuid, startDate, endDate],
    queryFn: async () => {
      const response = await apiAxios.get<{ data: EmployeeLedgerEntry[] }>(
        `/reports/employees/${employeeUuid}/ledger`,
        {
          params: { start_date: startDate, end_date: endDate },
        }
      )
      return response.data.data
    },
    enabled: enabled && !!employeeUuid && !!startDate && !!endDate,
  })
}
