<template>
  <v-navigation-drawer
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    location="right"
    temporary
    width="600"
    class="drawer employee-contribution-drawer"
  >
    <v-card class="h-100 d-flex flex-column" flat>
      <v-card-title class="employee-drawer-header flex-shrink-0 py-3 px-5">
        <div class="d-flex align-center w-100">
          <v-avatar color="primary" size="40" class="me-3">
            <v-icon color="white">mdi-account-cash</v-icon>
          </v-avatar>
          <div class="flex-grow-1">
            <div class="text-h6 font-weight-bold">Assign Contributions</div>
            <div class="text-caption text-medium-emphasis mt-1">
              {{ employeeName ? `Assign contributions to ${employeeName}` : 'Assign contributions to employee' }}
            </div>
          </div>
          <v-btn
            icon="mdi-close"
            variant="text"
            size="small"
            @click="$emit('update:modelValue', false)"
            class="ml-2"
          ></v-btn>
        </div>
      </v-card-title>

      <v-divider class="flex-shrink-0"></v-divider>

      <v-card-text class="flex-grow-1 overflow-y-auto pa-6" style="min-height: 0;">
        <!-- Loading State -->
        <div v-if="isLoading || isContributionsLoading" class="text-center py-8">
          <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
          <p class="mt-3 text-body-2 text-medium-emphasis">Loading...</p>
        </div>

        <!-- Error State -->
        <v-alert
          v-else-if="error || contributionsError"
          type="error"
          variant="tonal"
          class="mb-4"
          rounded="lg"
          density="compact"
        >
          <div class="d-flex align-center">
            <v-icon class="me-3">mdi-alert-circle</v-icon>
            <div class="flex-grow-1">
              <div class="font-weight-medium">Failed to load data</div>
              <div class="text-caption">{{ (error || contributionsError)?.message }}</div>
            </div>
            <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
          </div>
        </v-alert>

        <!-- Content -->
        <div v-else>
          <!-- Available Contributions -->
          <div class="mb-6">
            <div class="text-subtitle-1 font-weight-bold mb-3">Available Contributions</div>
            <v-data-table
              v-if="availableContributions.length > 0"
              :items="availableContributions"
              :headers="contributionHeaders"
              density="compact"
              item-key="uuid"
              class="contributions-table"
              :hide-no-data="true"
            >
              <template v-slot:[`item.employee_share`]="{ item }">
                {{ item.employee_share }}%
              </template>

              <template v-slot:[`item.employer_share`]="{ item }">
                {{ item.employer_share }}%
              </template>

              <template v-slot:[`item.actions`]="{ item }">
                <v-btn
                  color="primary"
                  size="small"
                  variant="outlined"
                  prepend-icon="mdi-plus"
                  @click="assignContribution(item)"
                  :loading="assigningContribution === item.uuid"
                >
                  Assign
                </v-btn>
              </template>
            </v-data-table>
            <v-alert v-else type="info" variant="tonal" density="compact">
              No contributions available. Create contributions first.
            </v-alert>
          </div>

          <!-- Assigned Contributions -->
          <div>
            <div class="text-subtitle-1 font-weight-bold mb-3">Assigned Contributions</div>
            <v-data-table
              v-if="assignedContributions.length > 0"
              :items="assignedContributions"
              :headers="assignedHeaders"
              density="compact"
              item-key="id"
              class="contributions-table"
              :hide-no-data="true"
            >
              <template v-slot:[`item.contribution.employee_share`]="{ item }">
                {{ item.contribution?.employee_share }}%
              </template>

              <template v-slot:[`item.contribution.employer_share`]="{ item }">
                {{ item.contribution?.employer_share }}%
              </template>

              <template v-slot:[`item.actions`]="{ item }">
                <v-btn
                  color="error"
                  size="small"
                  variant="text"
                  icon="mdi-delete"
                  @click="removeContribution(item)"
                  :loading="removingContribution === item.id"
                ></v-btn>
              </template>
            </v-data-table>
            <v-alert v-else type="info" variant="tonal" density="compact">
              No contributions assigned to this employee.
            </v-alert>
          </div>
        </div>
      </v-card-text>
    </v-card>
  </v-navigation-drawer>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useEmployeeContributions, useAssignContribution, useRemoveContribution } from '@/composables/useEmployeeContributions'
import { useContributions } from '@/composables/useContributions'
import type { EmployeeContribution } from '@/types/contribution'
import type { Contribution } from '@/types/contribution'
import { useNotification } from '@/composables/useNotification'

interface Props {
  modelValue: boolean
  employeeUuid: string | null
  employeeName?: string
}

const props = withDefaults(defineProps<Props>(), {
  employeeName: '',
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'success': []
}>()

const { showNotification } = useNotification()

// Employee contributions
const employeeUuidRef = computed(() => props.employeeUuid)
const { data: employeeContributionsData, isLoading, error, refetch } = useEmployeeContributions(employeeUuidRef)
const assignMutation = useAssignContribution()
const removeMutation = useRemoveContribution()

// All contributions
const { data: contributionsData, isLoading: isContributionsLoading, error: contributionsError } = useContributions(1)

const assignedContributions = computed(() => employeeContributionsData.value || [])
const allContributions = computed(() => contributionsData.value?.data || [])

// Filter out already assigned contributions
const availableContributions = computed(() => {
  const assignedIds = new Set(assignedContributions.value.map(ec => ec.contribution?.uuid).filter(Boolean))
  return allContributions.filter(c => !assignedIds.has(c.uuid))
})

const assigningContribution = ref<string | null>(null)
const removingContribution = ref<number | null>(null)

const contributionHeaders = [
  { title: 'Name', key: 'name', sortable: true },
  { title: 'Employee Share (%)', key: 'employee_share', sortable: true, align: 'end' as const },
  { title: 'Employer Share (%)', key: 'employer_share', sortable: true, align: 'end' as const },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
]

const assignedHeaders = [
  { title: 'Name', key: 'contribution.name', sortable: true },
  { title: 'Employee Share (%)', key: 'contribution.employee_share', sortable: true, align: 'end' as const },
  { title: 'Employer Share (%)', key: 'contribution.employer_share', sortable: true, align: 'end' as const },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
]

const assignContribution = async (contribution: Contribution) => {
  if (!props.employeeUuid) return
  
  assigningContribution.value = contribution.uuid
  try {
    await assignMutation.mutateAsync({
      employee_uuid: props.employeeUuid,
      contribution_uuid: contribution.uuid,
    })
    showNotification('Contribution assigned successfully', 'success')
    refetch()
    emit('success')
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    const message = err?.response?.data?.message || 'Failed to assign contribution'
    showNotification(message, 'error')
  } finally {
    assigningContribution.value = null
  }
}

const removeContribution = async (employeeContribution: EmployeeContribution) => {
  if (!props.employeeUuid || !employeeContribution.contribution?.uuid) return
  
  removingContribution.value = employeeContribution.id
  try {
    await removeMutation.mutateAsync({
      employeeUuid: props.employeeUuid,
      contributionUuid: employeeContribution.contribution.uuid,
    })
    showNotification('Contribution removed successfully', 'success')
    refetch()
    emit('success')
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    const message = err?.response?.data?.message || 'Failed to remove contribution'
    showNotification(message, 'error')
  } finally {
    removingContribution.value = null
  }
}
</script>

<style scoped>
.contributions-table :deep(.v-table__wrapper) {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 8px;
}

.contributions-table :deep(thead) {
  background-color: #f5f5f5;
}

.contributions-table :deep(th) {
  font-weight: bold;
  font-size: 0.875rem;
}

.contributions-table :deep(td) {
  font-size: 0.875rem;
}

.contributions-table :deep(tbody tr:hover) {
  background-color: #f9f9f9;
}
</style>
