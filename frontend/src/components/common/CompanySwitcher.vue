<template>
  <v-select
    v-model="selectedCompanyUuid"
    :items="companyItems"
    item-value="uuid"
    item-title="name"
    placeholder="Select Company"
    variant="outlined"
    density="compact"
    prepend-inner-icon="mdi-office-building"
    hide-details="auto"
    :loading="isLoadingCompanies"
    @update:model-value="handleCompanyChange"
    class="company-switcher"
  >
    <template #no-data>
      <div class="pa-4 text-center">
        <div v-if="isLoadingCompanies">Loading companies...</div>
        <div v-else>No companies available</div>
      </div>
    </template>
  </v-select>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue'
import { useCompanyStore } from '@/stores/company'
import { useRouter } from 'vue-router'

const companyStore = useCompanyStore()
const router = useRouter()

const selectedCompanyUuid = computed({
  get: () => companyStore.activeCompanyUuid,
  set: (value: string | null) => {
    companyStore.setActiveCompany(value)
  },
})

const companyItems = computed(() => {
  return companyStore.companies || []
})

const isLoadingCompanies = computed(() => companyStore.isLoadingCompanies)

const handleCompanyChange = (uuid: string | null) => {
  if (uuid) {
    companyStore.setActiveCompany(uuid)
    // Force a hard reload to refresh all data with new company context
    window.location.reload()
  }
}

// Fetch companies on mount
watch(
  () => companyStore.companies,
  (companies) => {
    if (!companies || companies.length === 0) {
      companyStore.fetchCompanies()
    }
  },
  { immediate: true }
)
</script>

<style scoped>
.company-switcher {
  min-width: 200px;
  max-width: 300px;
}
</style>
