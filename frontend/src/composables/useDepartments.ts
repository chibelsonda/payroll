import { computed, type Ref } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { Department } from '@/types/department'

const fetchDepartments = async (companyUuid?: string | null): Promise<Department[]> => {
  const params = companyUuid ? { company_uuid: companyUuid } : {}
  const response = await axios.get('/departments', { params })
  return response.data.data || []
}

export const useDepartments = (companyUuid: Ref<string | null | undefined> | (() => string | null | undefined) | string | null | undefined = undefined) => {
  const companyUuidValue = typeof companyUuid === 'function' 
    ? computed(companyUuid)
    : typeof companyUuid === 'object' && companyUuid !== null
    ? companyUuid
    : computed(() => companyUuid)

  return useQuery({
    queryKey: ['departments', computed(() => companyUuidValue.value)],
    queryFn: () => fetchDepartments(companyUuidValue.value ?? undefined),
    enabled: computed(() => companyUuidValue.value !== null && companyUuidValue.value !== undefined), // Only fetch when company is selected
    staleTime: 5 * 60 * 1000, // Cache for 5 minutes
  })
}
