import { computed, type Ref } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { Position } from '@/types/position'

const fetchPositions = async (departmentUuid?: string | null): Promise<Position[]> => {
  const params = departmentUuid ? { department_uuid: departmentUuid } : {}
  const response = await axios.get('/positions', { params })
  return response.data.data || []
}

export const usePositions = (departmentUuid: Ref<string | null | undefined> | (() => string | null | undefined) | string | null | undefined = undefined) => {
  const departmentUuidValue = typeof departmentUuid === 'function'
    ? computed(departmentUuid)
    : typeof departmentUuid === 'object' && departmentUuid !== null
    ? departmentUuid
    : computed(() => departmentUuid)

  return useQuery({
    queryKey: ['positions', computed(() => departmentUuidValue.value)],
    queryFn: () => fetchPositions(departmentUuidValue.value ?? undefined),
    enabled: computed(() => departmentUuidValue.value !== null && departmentUuidValue.value !== undefined), // Only fetch when department is selected
  })
}
