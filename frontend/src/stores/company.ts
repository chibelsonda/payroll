import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useCompanies } from '@/composables'
import type { Company } from '@/types/company'

const STORAGE_KEY = 'active_company_uuid'

export const useCompanyStore = defineStore('company', () => {
  // State
  const activeCompanyUuid = ref<string | null>(null)
  const { data: companies, isLoading: isLoadingCompanies, refetch: refetchCompanies } = useCompanies()

  // Load active company from localStorage on initialization
  const loadFromStorage = () => {
    const stored = localStorage.getItem(STORAGE_KEY)
    if (stored) {
      activeCompanyUuid.value = stored
    }
  }

  // Initialize from localStorage
  loadFromStorage()

  // Computed
  const activeCompany = computed<Company | undefined>(() => {
    if (!activeCompanyUuid.value || !companies.value) {
      return undefined
    }
    return companies.value.find((c) => c.uuid === activeCompanyUuid.value)
  })

  const hasActiveCompany = computed(() => !!activeCompanyUuid.value)

  // Actions
  const setActiveCompany = (uuid: string | null) => {
    activeCompanyUuid.value = uuid
    if (uuid) {
      localStorage.setItem(STORAGE_KEY, uuid)
    } else {
      localStorage.removeItem(STORAGE_KEY)
    }
  }

  const clearActiveCompany = () => {
    setActiveCompany(null)
  }

  const fetchCompanies = async () => {
    await refetchCompanies()
  }

  return {
    // State
    activeCompanyUuid: computed(() => activeCompanyUuid.value),
    companies,
    activeCompany,
    hasActiveCompany,
    isLoadingCompanies,
    // Actions
    setActiveCompany,
    clearActiveCompany,
    fetchCompanies,
  }
})
