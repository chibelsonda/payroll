import type { Ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { Loan, LoanPayment } from '@/types/loan'
import type { PaginationMeta } from '@/types/pagination'

// API functions
const fetchLoans = async (page = 1): Promise<{ data: Loan[]; meta: PaginationMeta }> => {
  const response = await axios.get(`/loans?page=${page}`)
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

const fetchLoan = async (uuid: string): Promise<Loan> => {
  const response = await axios.get(`/loans/${uuid}`)
  return response.data.data
}

const fetchLoanPayments = async (loanUuid: string): Promise<LoanPayment[]> => {
  const response = await axios.get(`/loans/${loanUuid}/payments`)
  return response.data.data || []
}

const createLoan = async (data: {
  employee_uuid: string
  amount: number
  balance: number
  start_date: string
}): Promise<Loan> => {
  const response = await axios.post('/loans', data)
  return response.data.data
}

const updateLoan = async (uuid: string, data: {
  amount?: number
  balance?: number
  start_date?: string
}): Promise<Loan> => {
  const response = await axios.put(`/loans/${uuid}`, data)
  return response.data.data
}

const deleteLoan = async (uuid: string): Promise<void> => {
  await axios.delete(`/loans/${uuid}`)
}

// Composables
export const useLoans = (page = 1, keepPreviousData = true) => {
  return useQuery({
    queryKey: ['loans', page],
    queryFn: () => fetchLoans(page),
    placeholderData: keepPreviousData ? (previousData) => previousData : undefined,
    retry: false,
    // refetchOnMount is true by default (set in main.ts)
  })
}

export const useLoan = (uuid: string | Ref<string | null>) => {
  const uuidValue = typeof uuid === 'string' ? uuid : uuid.value
  return useQuery({
    queryKey: ['loan', uuidValue],
    queryFn: () => fetchLoan(uuidValue!),
    enabled: !!uuidValue,
    retry: false,
    // refetchOnMount is true by default (set in main.ts)
  })
}

export const useLoanPayments = (loanUuid: string | Ref<string | null>) => {
  const loanUuidValue = typeof loanUuid === 'string' ? loanUuid : loanUuid.value
  return useQuery({
    queryKey: ['loan-payments', loanUuidValue],
    queryFn: () => fetchLoanPayments(loanUuidValue!),
    enabled: !!loanUuidValue,
    retry: false,
    // refetchOnMount is true by default (set in main.ts)
  })
}

export const useCreateLoan = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: createLoan,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['loans'] })
    },
  })
}

export const useUpdateLoan = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Parameters<typeof updateLoan>[1] }) =>
      updateLoan(uuid, data),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['loans'] })
      queryClient.invalidateQueries({ queryKey: ['loan', variables.uuid] })
    },
  })
}

export const useDeleteLoan = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: deleteLoan,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['loans'] })
    },
  })
}
