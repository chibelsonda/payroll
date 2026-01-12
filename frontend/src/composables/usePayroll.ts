import { computed, toValue, type Ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { PayrollRun, Payroll } from '@/types/payroll'
import type { PaginationMeta } from '@/types/pagination'

// API functions
const fetchPayrollRuns = async (page = 1): Promise<{ data: PayrollRun[]; meta: PaginationMeta }> => {
  const response = await axios.get(`/payroll-runs?page=${page}`)
  const paginationMeta = response.data.meta?.pagination || {}
  return {
    data: response.data.data || [],
    meta: {
      current_page: paginationMeta.current_page || page,
      from: paginationMeta.from ?? null,
      last_page: paginationMeta.last_page || 1,
      per_page: paginationMeta.per_page || 10,
      to: paginationMeta.to ?? null,
      total: paginationMeta.total || 0,
    },
  }
}

const fetchPayrollRun = async (uuid: string): Promise<PayrollRun> => {
  const response = await axios.get(`/payroll-runs/${uuid}`)
  return response.data.data
}

const fetchPayrolls = async (payrollRunUuid: string): Promise<Payroll[]> => {
  const response = await axios.get(`/payroll-runs/${payrollRunUuid}/payrolls`)
  return response.data.data || []
}

const fetchPayroll = async (uuid: string): Promise<Payroll> => {
  const response = await axios.get(`/payrolls/${uuid}`)
  return response.data.data
}

const createPayrollRun = async (data: {
  company_uuid: string
  period_start: string
  period_end: string
  pay_date: string
  status?: 'draft' | 'processed' | 'paid'
}): Promise<PayrollRun> => {
  const response = await axios.post('/payroll-runs', data)
  return response.data.data
}

const generatePayroll = async (payrollRunUuid: string): Promise<Payroll[]> => {
  const response = await axios.post(`/payroll-runs/${payrollRunUuid}/generate`)
  return response.data.data || []
}

const finalizePayroll = async (payrollRunUuid: string): Promise<PayrollRun> => {
  const response = await axios.post(`/payroll-runs/${payrollRunUuid}/finalize`)
  return response.data.data
}

const exportPayrollRun = async (payrollRunUuid: string, periodStart: string, periodEnd: string): Promise<void> => {
  try {
    const response = await axios.get(`/payroll-runs/${payrollRunUuid}/export-excel`, {
      responseType: 'blob',
    })
    
    // Create blob URL and trigger download
    const url = window.URL.createObjectURL(response.data)
    const link = document.createElement('a')
    link.href = url
    const filename = `payroll_${periodStart.replace(/-/g, '_')}_${periodEnd.replace(/-/g, '_')}.xlsx`
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error: any) {
    // Handle blob error responses (JSON errors wrapped in blob)
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const errorData = JSON.parse(text)
        throw new Error(errorData.message || 'Failed to export payroll')
      } catch (parseError) {
        throw new Error('Failed to export payroll')
      }
    }
    // Handle regular JSON error responses
    if (error.response?.data?.message) {
      throw new Error(error.response.data.message)
    }
    throw error
  }
}

// Composables
export const usePayrollRuns = (page = 1, keepPreviousData = true) => {
  return useQuery({
    queryKey: ['payroll-runs', page],
    queryFn: () => fetchPayrollRuns(page),
    placeholderData: keepPreviousData ? (previousData) => previousData : undefined,
    retry: false,
    // refetchOnMount is true by default (set in main.ts)
  })
}

export const usePayrollRun = (uuid: string | Ref<string | null | undefined> | (() => string | null | undefined) | null | undefined) => {
  const uuidValue = typeof uuid === 'function'
    ? computed(uuid)
    : typeof uuid === 'object' && uuid !== null
    ? uuid
    : computed(() => uuid)

  const uuidPrimitive = computed(() => toValue(uuidValue) ?? null)

  return useQuery({
    queryKey: computed(() => ['payroll-run', uuidPrimitive.value] as const),
    queryFn: () => {
      const uuid = uuidPrimitive.value
      if (!uuid) throw new Error('UUID is required')
      return fetchPayrollRun(uuid)
    },
    enabled: computed(() => !!uuidPrimitive.value),
  })
}

export const usePayrolls = (payrollRunUuid: string | Ref<string | null | undefined> | (() => string | null | undefined) | null | undefined) => {
  const uuidValue = typeof payrollRunUuid === 'function'
    ? computed(payrollRunUuid)
    : typeof payrollRunUuid === 'object' && payrollRunUuid !== null
    ? payrollRunUuid
    : computed(() => payrollRunUuid)

  const uuidPrimitive = computed(() => toValue(uuidValue) ?? null)

  return useQuery({
    queryKey: computed(() => ['payrolls', uuidPrimitive.value] as const),
    queryFn: () => {
      const uuid = uuidPrimitive.value
      if (!uuid) throw new Error('Payroll run UUID is required')
      return fetchPayrolls(uuid)
    },
    enabled: computed(() => !!uuidPrimitive.value),
  })
}

export const useCreatePayrollRun = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: createPayrollRun,
    onSuccess: () => {
      queryClient.invalidateQueries({
        queryKey: ['payroll-runs'],
        exact: false,
        refetchType: 'active',
      })
    },
  })
}

export const useGeneratePayroll = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: generatePayroll,
    onSuccess: (_, payrollRunUuid) => {
      queryClient.invalidateQueries({
        queryKey: ['payroll-runs'],
        exact: false,
        refetchType: 'active',
      })
      queryClient.invalidateQueries({
        queryKey: ['payrolls', payrollRunUuid],
        exact: false,
        refetchType: 'active',
      })
    },
  })
}

export const useFinalizePayroll = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: finalizePayroll,
    onSuccess: (data) => {
      queryClient.invalidateQueries({
        queryKey: ['payroll-runs'],
        exact: false,
        refetchType: 'active',
      })
      queryClient.setQueryData(['payroll-run', data.uuid], data)
    },
  })
}

export const useExportPayrollRun = () => {
  return useMutation({
    mutationFn: ({ payrollRunUuid, periodStart, periodEnd }: { payrollRunUuid: string; periodStart: string; periodEnd: string }) =>
      exportPayrollRun(payrollRunUuid, periodStart, periodEnd),
  })
}

export const usePayroll = (uuid: string | Ref<string | null | undefined> | (() => string | null | undefined) | null | undefined) => {
  const uuidValue = typeof uuid === 'function'
    ? computed(uuid)
    : typeof uuid === 'object' && uuid !== null
    ? uuid
    : computed(() => uuid)

  const uuidPrimitive = computed(() => toValue(uuidValue) ?? null)

  return useQuery({
    queryKey: computed(() => ['payroll', uuidPrimitive.value] as const),
    queryFn: () => {
      const uuid = uuidPrimitive.value
      if (!uuid) throw new Error('UUID is required')
      return fetchPayroll(uuid)
    },
    enabled: computed(() => !!uuidPrimitive.value),
  })
}
