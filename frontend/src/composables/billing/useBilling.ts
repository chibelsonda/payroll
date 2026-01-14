import { ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { Plan, Subscription, Payment, SubscribeRequest } from '@/types/billing'
import type { PaginationMeta } from '@/types/pagination'

// API functions
const fetchPlans = async (): Promise<Plan[]> => {
  const response = await axios.get('/billing/plans')
  return response.data.data || []
}

const fetchSubscription = async (): Promise<Subscription | null> => {
  const response = await axios.get('/billing/subscription')
  return response.data.data || null
}

const fetchPayments = async (page = 1): Promise<{ data: Payment[]; meta: PaginationMeta }> => {
  const response = await axios.get(`/billing/payments?page=${page}`)
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

const subscribe = async (data: SubscribeRequest): Promise<{ subscription: Subscription; payment: Payment; checkout_url: string }> => {
  const response = await axios.post('/billing/subscribe', data)
  return response.data.data
}

// Composables
export const usePlans = () => {
  return useQuery({
    queryKey: ['billing', 'plans'],
    queryFn: fetchPlans,
    retry: false,
  })
}

export const useSubscription = () => {
  return useQuery({
    queryKey: ['billing', 'subscription'],
    queryFn: fetchSubscription,
    retry: false,
  })
}

export const usePayments = (page = 1, keepPreviousData = true) => {
  return useQuery({
    queryKey: ['billing', 'payments', page],
    queryFn: () => fetchPayments(page),
    placeholderData: keepPreviousData ? (previousData) => previousData : undefined,
    retry: false,
  })
}

export const useSubscribe = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: subscribe,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['billing', 'subscription'] })
      queryClient.invalidateQueries({ queryKey: ['billing', 'payments'] })
    },
  })
}
