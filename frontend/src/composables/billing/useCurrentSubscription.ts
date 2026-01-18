import { useQuery } from '@tanstack/vue-query'
import apiAxios from '@/lib/axios'
import type { Subscription } from '@/types/billing'

const fetchCurrentSubscription = async (): Promise<Subscription | null> => {
  const response = await apiAxios.get('/billing/current-subscription')
  return (response.data?.data as Subscription) ?? null
}

export const useCurrentSubscription = () => {
  return useQuery<Subscription | null>({
    queryKey: ['billing', 'current-subscription'],
    queryFn: fetchCurrentSubscription,
    staleTime: 1000 * 30,
    retry: false,
  })
}
