<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center justify-space-between flex-wrap w-100">
              <h1 class="text-h6 font-weight-bold mb-0">Salary Management</h1>
              <v-btn
                color="primary"
                size="small"
                prepend-icon="mdi-plus"
                @click="openDrawer"
              >
                Add Salary
              </v-btn>
            </div>
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-4">
            <!-- Employee Selection -->
            <div class="mb-6">
              <div class="text-body-2 mb-2 font-weight-medium">Select Employee</div>
              <v-select
                v-model="selectedEmployeeUuid"
                :items="employees || []"
                item-value="uuid"
                item-title="display_name"
                placeholder="Select employee to view salary history"
                prepend-inner-icon="mdi-account"
                variant="outlined"
                density="compact"
                hide-details="auto"
                class="employee-form-field v-select"
                @update:model-value="loadEmployeeSalaries"
              />
            </div>

            <!-- Loading State -->
            <div v-if="isLoading && selectedEmployeeUuid" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
              <p class="mt-3 text-body-2 text-medium-emphasis">Loading salary history...</p>
            </div>

            <!-- Error State -->
            <v-alert
              v-else-if="error"
              type="error"
              variant="tonal"
              class="mb-4"
              rounded="lg"
              density="compact"
            >
              <div class="d-flex align-center">
                <v-icon class="me-3">mdi-alert-circle</v-icon>
                <div class="flex-grow-1">
                  <div class="font-weight-medium">Failed to load salary history</div>
                  <div class="text-caption">{{ error.message }}</div>
                </div>
                <v-btn variant="text" color="error" @click="loadEmployeeSalaries">Retry</v-btn>
              </div>
            </v-alert>

            <!-- Salary History Table -->
            <v-data-table
              v-else-if="selectedEmployeeUuid"
              :items="salaries"
              :headers="headers"
              :loading="isLoading"
              density="compact"
              item-key="uuid"
              class="salary-table"
              :items-per-page="15"
              :items-per-page-options="[10, 15, 25, 50]"
              :hide-no-data="true"
              :no-data-text="''"
              :row-height="40"
            >
              <template v-slot:[`body.append`]>
                <tr v-if="salaries.length === 0">
                  <td :colspan="headers.length" class="text-center py-8">
                    <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-cash</v-icon>
                    <p class="text-subtitle-1 font-weight-medium mb-1">No salary records yet</p>
                    <p class="text-body-2 text-medium-emphasis mb-3">Start by adding a salary record</p>
                    <v-btn
                      color="primary"
                      size="small"
                      prepend-icon="mdi-plus"
                      @click="openDrawer"
                    >
                      Add Salary
                    </v-btn>
                  </td>
                </tr>
              </template>

              <template v-slot:[`item.amount`]="{ item }">
                {{ formatCurrency(item.amount) }}
              </template>

              <template v-slot:[`item.effective_from`]="{ item }">
                {{ formatDate(item.effective_from) }}
              </template>

              <template v-slot:[`item.actions`]="{ item }">
                <v-menu>
                  <template #activator="{ props }">
                    <v-btn
                      icon="mdi-dots-vertical"
                      size="small"
                      variant="text"
                      v-bind="props"
                      class="action-btn"
                    ></v-btn>
                  </template>
                  <v-list density="compact">
                    <v-list-item
                      prepend-icon="mdi-pencil"
                      title="Edit"
                      @click="editSalary(item)"
                    ></v-list-item>
                    <v-list-item
                      prepend-icon="mdi-delete"
                      title="Delete"
                      @click="confirmDelete(item)"
                    ></v-list-item>
                  </v-list>
                </v-menu>
              </template>
            </v-data-table>

            <!-- Empty State (No Employee Selected) -->
            <div v-else class="text-center py-12">
              <v-icon size="64" color="grey-lighten-1" class="mb-4">mdi-account-search</v-icon>
              <p class="text-subtitle-1 font-weight-medium mb-2">Select an employee</p>
              <p class="text-body-2 text-medium-emphasis">Choose an employee from the dropdown to view their salary history</p>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Salary Form Drawer -->
    <v-navigation-drawer
      :model-value="drawer"
      @update:model-value="drawer = $event"
      location="right"
      temporary
      width="500"
      class="drawer salary-drawer"
    >
      <v-card class="h-100 d-flex flex-column" flat>
        <v-card-title class="employee-drawer-header flex-shrink-0 py-3 px-5">
          <div class="d-flex align-center w-100">
            <v-avatar color="primary" size="40" class="me-3">
              <v-icon color="white">mdi-cash</v-icon>
            </v-avatar>
            <div class="flex-grow-1">
              <div class="text-h6 font-weight-bold">{{ editingSalary ? 'Edit Salary' : 'Add Salary' }}</div>
              <div class="text-caption text-medium-emphasis mt-1">
                {{ editingSalary ? 'Update salary record' : 'Add a new salary record for employee' }}
              </div>
            </div>
            <v-btn icon="mdi-close" variant="text" size="small" @click="closeDrawer" class="ml-2"></v-btn>
          </div>
        </v-card-title>

        <v-divider class="flex-shrink-0"></v-divider>

        <v-form ref="formRef" @submit.prevent="handleFormSubmit" class="d-flex flex-column flex-grow-1" style="min-height: 0;">
          <v-card-text class="flex-grow-1 overflow-y-auto pa-6" style="min-height: 0;">
            <SalaryForm
              ref="salaryFormRef"
              :salary="editingSalary"
              :selected-employee-uuid="selectedEmployeeUuid"
            />
          </v-card-text>

          <v-divider class="flex-shrink-0"></v-divider>

          <v-card-actions class="pa-4 flex-shrink-0 bg-grey-lighten-5">
            <v-btn
              type="button"
              variant="outlined"
              @click="closeDrawer"
              class="flex-grow-1"
              size="small"
            >
              Cancel
            </v-btn>
            <v-spacer class="mx-2"></v-spacer>
            <v-btn
              type="submit"
              color="primary"
              variant="flat"
              size="small"
              :loading="isSubmitting"
              :disabled="!isValid"
              class="flex-grow-1"
              :prepend-icon="editingSalary ? 'mdi-content-save' : 'mdi-check'"
            >
              {{ editingSalary ? 'Update Salary' : 'Create Salary' }}
            </v-btn>
          </v-card-actions>
        </v-form>
      </v-card>
    </v-navigation-drawer>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card>
        <v-card-title class="text-h6">Confirm Delete</v-card-title>
        <v-card-text>
          Are you sure you want to delete this salary record? This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" @click="handleDelete" :loading="deleteMutation.isPending.value">
            Delete
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useEmployeeSalaries, useCreateSalary, useUpdateSalary, useDeleteSalary } from '@/composables'
import { useEmployees } from '@/composables'
import type { Salary } from '@/types/salary'
import SalaryForm from './SalaryForm.vue'
import { useNotification } from '@/composables'

const selectedEmployeeUuid = ref<string | null>(null)
const drawer = ref(false)
const editingSalary = ref<Salary | null>(null)
const formRef = ref()
const salaryFormRef = ref<InstanceType<typeof SalaryForm> | null>(null)
const deleteDialog = ref(false)
const salaryToDelete = ref<Salary | null>(null)

const { data: employeesData } = useEmployees()
const { data: salariesData, isLoading, error, refetch } = useEmployeeSalaries(selectedEmployeeUuid)
const { showNotification } = useNotification()
const createSalary = useCreateSalary()
const updateSalary = useUpdateSalary()
const deleteSalary = useDeleteSalary()

const employees = computed(() => {
  if (!employeesData.value?.data) return []
  return employeesData.value.data.map((emp) => ({
    uuid: emp.uuid,
    display_name: `${emp.user?.first_name} ${emp.user?.last_name} (${emp.employee_no})`,
  }))
})

const salaries = computed(() => salariesData.value || [])
const isSubmitting = computed(() => salaryFormRef.value?.isSubmitting || false)
const isValid = computed(() => salaryFormRef.value?.isValid || false)

const headers = [
  { title: 'Amount', key: 'amount', sortable: true, align: 'end' as const },
  { title: 'Effective From', key: 'effective_from', sortable: true },
  { title: 'Created At', key: 'created_at', sortable: true },
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

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const loadEmployeeSalaries = () => {
  if (selectedEmployeeUuid.value) {
    refetch()
  }
}

const openDrawer = () => {
  if (!selectedEmployeeUuid.value) {
    showNotification('Please select an employee first', 'warning')
    return
  }
  editingSalary.value = null
  drawer.value = true
}

const closeDrawer = () => {
  drawer.value = false
  editingSalary.value = null
}

const editSalary = (salary: Salary) => {
  editingSalary.value = salary
  drawer.value = true
}

const handleFormSubmit = async () => {
  if (salaryFormRef.value) {
    await salaryFormRef.value.handleSubmit()
    closeDrawer()
    loadEmployeeSalaries()
  }
}

const confirmDelete = (salary: Salary) => {
  salaryToDelete.value = salary
  deleteDialog.value = true
}

const deleteMutation = useDeleteSalary()

const handleDelete = async () => {
  if (salaryToDelete.value) {
    try {
      await deleteMutation.mutateAsync(salaryToDelete.value.uuid)
      showNotification('Salary deleted successfully', 'success')
      deleteDialog.value = false
      salaryToDelete.value = null
      loadEmployeeSalaries()
    } catch (error: unknown) {
      const err = error as { response?: { data?: { message?: string } } }
      const message = err?.response?.data?.message || 'Failed to delete salary'
      showNotification(message, 'error')
    }
  }
}
</script>

<style scoped>
.salary-table {
  border-radius: 8px;
  overflow: hidden;
}

.salary-table :deep(.v-data-table__thead th) {
  background-color: rgba(0, 0, 0, 0.03);
  font-weight: 600;
  font-size: 0.875rem;
  padding: 14px 16px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: rgba(0, 0, 0, 0.87);
}

.salary-table :deep(.v-data-table__tbody td) {
  padding: 14px 16px;
  font-size: 0.875rem;
}

.salary-table :deep(.v-data-table__tbody tr:hover) {
  background-color: rgba(25, 118, 210, 0.04);
}

.action-btn {
  opacity: 0.7;
}

.action-btn:hover {
  opacity: 1;
}
</style>
