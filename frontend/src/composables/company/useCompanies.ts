import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import apiAxios, { webAxios } from '@/lib/axios'
import type { Company } from '@/types/company'

export interface CreateCompanyData {
  name: string
  registration_no?: string
  address?: string
}

/**
 * Fetch companies accessible to the authenticated user
 */
export function fetchCompanies() {
  return apiAxios.get<{ data: Company[] }>('/companies').then((res) => res.data.data)
}

/**
 * Composable to fetch companies
 */
export function useCompanies() {
  return useQuery({
    queryKey: ['companies'],
    queryFn: fetchCompanies,
  })
}

/**
 * Composable to create a company
 */
export function useCreateCompany() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (data: CreateCompanyData) => {
      // Get CSRF cookie first (required for Sanctum)
      await webAxios.get('/sanctum/csrf-cookie')
      // Then make the POST request
      return apiAxios.post<{ data: Company }>('/companies', data).then((res) => res.data.data)
    },
    onSuccess: () => {
      // Invalidate companies query to refetch
      queryClient.invalidateQueries({ queryKey: ['companies'] })
      // Invalidate user query to get updated company_id
      queryClient.invalidateQueries({ queryKey: ['user'] })
    },
  })
}
