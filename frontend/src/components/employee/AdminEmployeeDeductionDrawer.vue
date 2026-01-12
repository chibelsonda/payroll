<template>
  <v-navigation-drawer
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    location="right"
    temporary
    width="600"
    class="drawer employee-deduction-drawer"
  >
    <v-card class="h-100 d-flex flex-column" flat>
      <v-card-title class="employee-drawer-header flex-shrink-0 py-3 px-5">
        <div class="d-flex align-center w-100">
          <v-avatar color="primary" size="40" class="me-3">
            <v-icon color="white">mdi-cash-minus</v-icon>
          </v-avatar>
          <div class="flex-grow-1">
            <div class="text-h6 font-weight-bold">Assign Deductions</div>
            <div class="text-caption text-medium-emphasis mt-1">
              {{ employeeName ? `Assign deductions to ${employeeName}` : 'Assign deductions to employee' }}
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
        <div v-if="isLoading || isDeductionsLoading" class="text-center py-8">
          <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
          <p class="mt-3 text-body-2 text-medium-emphasis">Loading...</p>
        </div>

        <!-- Error State -->
        <v-alert
          v-else-if="error || deductionsError"
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
              <div class="text-caption">{{ (error || deductionsError)?.message }}</div>
            </div>
            <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
          </div>
        </v-alert>

        <!-- Content -->
        <div v-else>
          <!-- Available Deductions -->
          <div class="mb-6">
            <div class="text-subtitle-1 font-weight-bold mb-3">Available Deductions</div>
            <v-data-table
              v-if="availableDeductions.length > 0"
              :items="availableDeductions"
              :headers="deductionHeaders"
              density="compact"
              item-key="uuid"
              class="deductions-table"
              :hide-no-data="true"
            >
              <template v-slot:[`item.type`]="{ item }">
                <v-chip size="small" :color="item.type === 'fixed' ? 'primary' : 'secondary'">
                  {{ item.type }}
                </v-chip>
              </template>

              <template v-slot:[`item.default_amount`]="{ item }">
                <span v-if="item.default_amount">
                  {{ item.type === 'fixed' ? formatCurrency(item.default_amount) : `${item.default_amount}%` }}
                </span>
                <span v-else class="text-medium-emphasis">-</span>
              </template>

              <template v-slot:[`item.actions`]="{ item }">
                <v-btn
                  color="primary"
                  size="small"
                  variant="outlined"
                  prepend-icon="mdi-plus"
                  @click="assignDeduction(item)"
                  :loading="assigningDeduction === item.uuid"
                >
                  Assign
                </v-btn>
              </template>
            </v-data-table>
            <v-alert v-else type="info" variant="tonal" density="compact">
              No deductions available. Create deductions first.
            </v-alert>
          </div>

          <!-- Assigned Deductions -->
          <div>
            <div class="text-subtitle-1 font-weight-bold mb-3">Assigned Deductions</div>
            <v-data-table
              v-if="assignedDeductions.length > 0"
              :items="assignedDeductions"
              :headers="assignedHeaders"
              density="compact"
              item-key="id"
              class="deductions-table"
              :hide-no-data="true"
            >
              <template v-slot:[`item.deduction.type`]="{ item }">
                <v-chip size="small" :color="item.deduction.type === 'fixed' ? 'primary' : 'secondary'">
                  {{ item.deduction.type }}
                </v-chip>
              </template>

              <template v-slot:[`item.amount`]="{ item }">
                {{ item.deduction.type === 'fixed' ? formatCurrency(item.amount) : `${item.amount}%` }}
              </template>

              <template v-slot:[`item.actions`]="{ item }">
                <v-btn
                  color="error"
                  size="small"
                  variant="text"
                  icon="mdi-delete"
                  @click="removeDeduction(item)"
                  :loading="removingDeduction === item.id"
                ></v-btn>
              </template>
            </v-data-table>
            <v-alert v-else type="info" variant="tonal" density="compact">
              No deductions assigned to this employee.
            </v-alert>
          </div>
        </div>
      </v-card-text>
    </v-card>
  </v-navigation-drawer>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useEmployeeDeductions, useAssignDeduction, useRemoveDeduction } from '@/composables'
import { useDeductions } from '@/composables'
import type { EmployeeDeduction } from '@/types/deduction'
import type { Deduction } from '@/types/deduction'
import { useNotification } from '@/composables'

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

// Employee deductions
const employeeUuidRef = computed(() => props.employeeUuid)
const { data: employeeDeductionsData, isLoading, error, refetch } = useEmployeeDeductions(employeeUuidRef)
const assignMutation = useAssignDeduction()
const removeMutation = useRemoveDeduction()

// All deductions
const { data: deductionsData, isLoading: isDeductionsLoading, error: deductionsError } = useDeductions(1)

const assignedDeductions = computed(() => employeeDeductionsData.value || [])
const allDeductions = computed(() => deductionsData.value?.data || [])

// Filter out already assigned deductions
const availableDeductions = computed(() => {
  const assignedIds = new Set(assignedDeductions.value.map(ed => ed.deduction?.uuid).filter(Boolean))
  return allDeductions.filter(d => !assignedIds.has(d.uuid))
})

const assigningDeduction = ref<string | null>(null)
const removingDeduction = ref<number | null>(null)

const deductionHeaders = [
  { title: 'Name', key: 'name', sortable: true },
  { title: 'Type', key: 'type', sortable: true },
  { title: 'Default Amount', key: 'default_amount', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
]

const assignedHeaders = [
  { title: 'Name', key: 'deduction.name', sortable: true },
  { title: 'Type', key: 'deduction.type', sortable: true },
  { title: 'Amount', key: 'amount', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
]

const formatCurrency = (value: string | number): string => {
  const num = typeof value === 'string' ? parseFloat(value) : value
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(num)
}

const assignDeduction = async (deduction: Deduction) => {
  if (!props.employeeUuid) return
  
  assigningDeduction.value = deduction.uuid
  try {
    // Use default_amount if available, otherwise use 0
    const amount = deduction.default_amount || (deduction.type === 'fixed' ? '0' : '0')
    
    await assignMutation.mutateAsync({
      employee_uuid: props.employeeUuid,
      deduction_uuid: deduction.uuid,
      amount: typeof amount === 'string' ? parseFloat(amount) : amount,
    })
    showNotification('Deduction assigned successfully', 'success')
    refetch()
    emit('success')
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    const message = err?.response?.data?.message || 'Failed to assign deduction'
    showNotification(message, 'error')
  } finally {
    assigningDeduction.value = null
  }
}

const removeDeduction = async (employeeDeduction: EmployeeDeduction) => {
  if (!props.employeeUuid || !employeeDeduction.deduction?.uuid) return
  
  removingDeduction.value = employeeDeduction.id
  try {
    await removeMutation.mutateAsync({
      employeeUuid: props.employeeUuid,
      deductionUuid: employeeDeduction.deduction.uuid,
    })
    showNotification('Deduction removed successfully', 'success')
    refetch()
    emit('success')
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    const message = err?.response?.data?.message || 'Failed to remove deduction'
    showNotification(message, 'error')
  } finally {
    removingDeduction.value = null
  }
}
</script>

<style scoped>
.deductions-table :deep(.v-table__wrapper) {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 8px;
}

.deductions-table :deep(thead) {
  background-color: #f5f5f5;
}

.deductions-table :deep(th) {
  font-weight: bold;
  font-size: 0.875rem;
}

.deductions-table :deep(td) {
  font-size: 0.875rem;
}

.deductions-table :deep(tbody tr:hover) {
  background-color: #f9f9f9;
}
</style>
