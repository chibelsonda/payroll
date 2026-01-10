import { useQuery } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { Company } from '@/types/company'

const fetchCompanies = async (): Promise<Company[]> => {
  const response = await axios.get('/companies')
  return response.data.data || []
}

export const useCompanies = () => {
  return useQuery({
    queryKey: ['companies'],
    queryFn: fetchCompanies,
    staleTime: 5 * 60 * 1000, // Cache for 5 minutes
  })
}
