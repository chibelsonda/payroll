<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center justify-space-between flex-wrap w-100">
              <h1 class="text-h6 font-weight-bold mb-0">Employee Allowances</h1>
              <v-btn color="primary" size="small" prepend-icon="mdi-plus" @click="openDrawer">
                Add Allowance
              </v-btn>
            </div>
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-0">
            <div v-if="isLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
              <p class="mt-3 text-body-2 text-medium-emphasis">Loading allowances...</p>
            </div>

            <v-alert v-else-if="error" type="error" variant="tonal" class="ma-4" rounded="lg" density="compact">
              <div class="d-flex align-center">
                <v-icon class="me-3">mdi-alert-circle</v-icon>
                <div class="flex-grow-1">
                  <div class="font-weight-medium">Failed to load allowances</div>
                  <div class="text-caption">{{ error.message }}</div>
                </div>
                <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
              </div>
            </v-alert>

            <v-data-table
              v-else
              :items="allowances"
              :headers="headers"
              :loading="isLoading"
              density="compact"
              item-key="uuid"
              class="allowances-table"
              :items-per-page="15"
              :items-per-page-options="[10, 15, 25, 50]"
            >
              <template v-slot:[`body.append`]>
                <tr v-if="allowances.length === 0">
                  <td :colspan="headers.length" class="text-center py-8">
                    <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-cash-plus</v-icon>
                    <p class="text-subtitle-1 font-weight-medium mb-1">No allowances yet</p>
                    <p class="text-body-2 text-medium-emphasis mb-3">Get started by adding an allowance</p>
                    <v-btn color="primary" size="small" prepend-icon="mdi-plus" @click="openDrawer">
                      Add Allowance
                    </v-btn>
                  </td>
                </tr>
              </template>

              <template v-slot:[`item.employee`]="{ item }">
                <span class="text-body-2">
                  {{ item.employee?.user?.first_name }} {{ item.employee?.user?.last_name }}
                  <span class="text-medium-emphasis">({{ item.employee?.employee_no }})</span>
                </span>
              </template>

              <template v-slot:[`item.type`]="{ item }">
                <v-chip size="small" variant="tonal" color="primary">
                  {{ item.type }}
                </v-chip>
              </template>

              <template v-slot:[`item.amount`]="{ item }">
                <span class="text-body-2 font-weight-medium">₱{{ formatCurrency(item.amount) }}</span>
              </template>

              <template v-slot:[`item.effective_from`]="{ item }">
                <span class="text-body-2">{{ item.effective_from ? formatDate(item.effective_from) : '-' }}</span>
              </template>

              <template v-slot:[`item.effective_to`]="{ item }">
                <span class="text-body-2">{{ item.effective_to ? formatDate(item.effective_to) : '-' }}</span>
              </template>

              <template v-slot:[`item.actions`]="{ item }">
                <v-btn icon="mdi-pencil" size="small" variant="text" @click="editAllowance(item)"></v-btn>
                <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="confirmDelete(item)"></v-btn>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Form Drawer -->
    <v-navigation-drawer v-model="drawer" location="right" width="500" temporary>
      <v-card class="d-flex flex-column h-100" elevation="0">
        <v-card-title class="px-4 py-3 flex-shrink-0">
          <div class="d-flex align-center justify-space-between w-100">
            <span class="text-h6">{{ editingAllowance ? 'Edit Allowance' : 'Add Allowance' }}</span>
            <v-btn icon="mdi-close" variant="text" size="small" @click="closeDrawer"></v-btn>
          </div>
        </v-card-title>

        <v-divider class="flex-shrink-0"></v-divider>

        <v-form ref="formRef" @submit.prevent="handleFormSubmit" class="d-flex flex-column flex-grow-1" style="min-height: 0;">
          <v-card-text class="flex-grow-1 overflow-y-auto pa-6" style="min-height: 0;">
            <EmployeeAllowanceForm ref="allowanceFormRef" :allowance="editingAllowance" />
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
              :prepend-icon="editingAllowance ? 'mdi-content-save' : 'mdi-check'"
            >
              {{ editingAllowance ? 'Update Allowance' : 'Create Allowance' }}
            </v-btn>
          </v-card-actions>
        </v-form>
      </v-card>
    </v-navigation-drawer>

    <!-- Delete Confirmation Dialog -->
    <ConfirmDialog
      v-model="deleteDialog"
      title="Delete Allowance"
      message="Are you sure you want to delete this allowance? This action cannot be undone."
      type="danger"
      :loading="isDeleting"
      @confirm="handleDelete"
      @cancel="deleteDialog = false"
    />
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useEmployeeAllowances } from '@/composables/allowance/useEmployeeAllowances'
import type { EmployeeAllowance } from '@/types/allowance'
import EmployeeAllowanceForm from './EmployeeAllowanceForm.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'

const drawer = ref(false)
const editingAllowance = ref<EmployeeAllowance | null>(null)
const formRef = ref()
const allowanceFormRef = ref<InstanceType<typeof EmployeeAllowanceForm> | null>(null)
const deleteDialog = ref(false)
const allowanceToDelete = ref<EmployeeAllowance | null>(null)

const { employeeAllowances, isLoading, error, refetch, createEmployeeAllowance, updateEmployeeAllowance, deleteEmployeeAllowance, isCreating, isUpdating, isDeleting } = useEmployeeAllowances()

const allowances = computed(() => employeeAllowances.value || [])

const headers = [
  { title: 'Employee', key: 'employee', sortable: true },
  { title: 'Type', key: 'type', sortable: true },
  { title: 'Description', key: 'description', sortable: false },
  { title: 'Amount', key: 'amount', sortable: true, align: 'end' as const },
  { title: 'Effective From', key: 'effective_from', sortable: true },
  { title: 'Effective To', key: 'effective_to', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
]

const isSubmitting = computed(() => isCreating.value || isUpdating.value)
const isValid = computed(() => allowanceFormRef.value?.isValid || false)

const formatCurrency = (value: number | string): string => {
  return new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number(value))
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const openDrawer = () => {
  editingAllowance.value = null
  drawer.value = true
}

const closeDrawer = () => {
  drawer.value = false
  editingAllowance.value = null
  allowanceFormRef.value?.resetForm()
}

const editAllowance = (allowance: EmployeeAllowance) => {
  editingAllowance.value = allowance
  drawer.value = true
}

const confirmDelete = (allowance: EmployeeAllowance) => {
  allowanceToDelete.value = allowance
  deleteDialog.value = true
}

const handleFormSubmit = async () => {
  if (!allowanceFormRef.value) return

  const values = allowanceFormRef.value.values

  try {
    if (editingAllowance.value) {
      await updateEmployeeAllowance({
        uuid: editingAllowance.value.uuid!,
        data: {
          type: values.type,
          description: values.description,
          amount: values.amount,
          effective_from: values.effective_from,
          effective_to: values.effective_to,
        },
      })
    } else {
      await createEmployeeAllowance({
        employee_uuid: values.employee_uuid,
        type: values.type,
        description: values.description,
        amount: values.amount,
        effective_from: values.effective_from,
        effective_to: values.effective_to,
      })
    }
    closeDrawer()
  } catch (error) {
    console.error('Error saving allowance:', error)
  }
}

const handleDelete = async () => {
  if (!allowanceToDelete.value?.uuid) return

  try {
    await deleteEmployeeAllowance(allowanceToDelete.value.uuid)
    deleteDialog.value = false
    allowanceToDelete.value = null
  } catch (error) {
    console.error('Error deleting allowance:', error)
  }
}
</script>
