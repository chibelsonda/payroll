<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center justify-space-between flex-wrap w-100">
              <h1 class="text-h6 font-weight-bold mb-0">Deductions Management</h1>
              <v-btn
                color="primary"
                size="small"
                prepend-icon="mdi-plus"
                @click="openDrawer"
              >
                Add Deduction
              </v-btn>
            </div>
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-0">
            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
              <p class="mt-3 text-body-2 text-medium-emphasis">Loading deductions...</p>
            </div>

            <!-- Error State -->
            <v-alert
              v-else-if="error"
              type="error"
              variant="tonal"
              class="ma-4"
              rounded="lg"
              density="compact"
            >
              <div class="d-flex align-center">
                <v-icon class="me-3">mdi-alert-circle</v-icon>
                <div class="flex-grow-1">
                  <div class="font-weight-medium">Failed to load deductions</div>
                  <div class="text-caption">{{ error.message }}</div>
                </div>
                <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
              </div>
            </v-alert>

            <!-- Deductions Table -->
            <v-data-table
              v-else
              v-model:page="page"
              :items="deductionsData"
              :headers="headers"
              :loading="isLoading"
              density="compact"
              item-key="uuid"
              class="deductions-table"
              :items-per-page="15"
              :items-per-page-options="[10, 15, 25, 50]"
              :hide-no-data="true"
              :no-data-text="''"
              :server-items-length="meta.total"
            >
              <template v-slot:[`body.append`]>
                <tr v-if="deductionsData.length === 0">
                  <td :colspan="headers.length" class="text-center py-8">
                    <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-cash-minus</v-icon>
                    <p class="text-subtitle-1 font-weight-medium mb-1">No deductions</p>
                    <p class="text-body-2 text-medium-emphasis mb-3">Start by adding a new deduction</p>
                    <v-btn
                      color="primary"
                      size="small"
                      prepend-icon="mdi-plus"
                      @click="openDrawer"
                    >
                      Add Deduction
                    </v-btn>
                  </td>
                </tr>
              </template>

              <template v-slot:[`item.type`]="{ item }">
                <v-chip :color="item.type === 'fixed' ? 'primary' : 'success'" size="small">
                  {{ item.type }}
                </v-chip>
              </template>

              <template v-slot:[`item.actions`]="{ item }">
                <v-menu location="bottom end">
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
                      @click="editDeduction(item)"
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
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Deduction Form Drawer -->
    <v-navigation-drawer
      v-model="drawer"
      location="right"
      temporary
      width="500"
      class="drawer deduction-drawer"
    >
      <v-card class="h-100 d-flex flex-column" flat>
        <v-card-title class="employee-drawer-header flex-shrink-0 py-3 px-5">
          <div class="d-flex align-center w-100">
            <v-avatar color="primary" size="40" class="me-3">
              <v-icon color="white">{{ editingDeduction ? 'mdi-pencil' : 'mdi-plus' }}</v-icon>
            </v-avatar>
            <div class="flex-grow-1">
              <div class="text-h6 font-weight-bold">{{ editingDeduction ? 'Edit Deduction' : 'Add Deduction' }}</div>
              <div class="text-caption text-medium-emphasis mt-1">
                {{ editingDeduction ? 'Update deduction information' : 'Add a new deduction to the system' }}
              </div>
            </div>
            <v-btn
              icon="mdi-close"
              variant="text"
              size="small"
              @click="closeDrawer"
              class="ml-2"
            ></v-btn>
          </div>
        </v-card-title>

        <v-divider class="flex-shrink-0"></v-divider>

        <v-form ref="formRef" @submit.prevent="handleFormSubmit" class="d-flex flex-column flex-grow-1" style="min-height: 0;">
          <v-card-text class="flex-grow-1 overflow-y-auto pa-6" style="min-height: 0;">
            <DeductionForm
              v-if="drawer"
              ref="deductionFormRef"
              :deduction="editingDeduction"
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
              :prepend-icon="editingDeduction ? 'mdi-content-save' : 'mdi-check'"
            >
              {{ editingDeduction ? 'Update Deduction' : 'Create Deduction' }}
            </v-btn>
          </v-card-actions>
        </v-form>
      </v-card>
    </v-navigation-drawer>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useDeductions, useDeleteDeduction } from '@/composables'
import type { Deduction } from '@/types/deduction'
import DeductionForm from './DeductionForm.vue'
import { useNotification } from '@/composables/useNotification'

const page = ref(1)
const drawer = ref(false)
const editingDeduction = ref<Deduction | null>(null)
const formRef = ref()
const deductionFormRef = ref<InstanceType<typeof DeductionForm> | null>(null)

const { data, isLoading, error, refetch } = useDeductions(page)
const { showNotification } = useNotification()
const deleteDeduction = useDeleteDeduction()

const deductionsData = computed(() => data.value?.data || [])
const meta = computed(() => data.value?.meta || {
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: null,
  to: null,
})

const isSubmitting = computed(() => deductionFormRef.value?.isSubmitting || false)
const isValid = computed(() => deductionFormRef.value?.isValid || false)

const headers = [
  { title: 'Name', key: 'name', align: 'start' as const, sortable: true },
  { title: 'Type', key: 'type', align: 'start' as const, sortable: true },
  { title: 'Actions', key: 'actions', align: 'end' as const, sortable: false },
]

const openDrawer = () => {
  editingDeduction.value = null
  drawer.value = true
}

const closeDrawer = () => {
  drawer.value = false
  editingDeduction.value = null
}

const editDeduction = (deduction: Deduction) => {
  editingDeduction.value = deduction
  drawer.value = true
}

const handleFormSubmit = async () => {
  if (deductionFormRef.value) {
    await deductionFormRef.value.handleSubmit()
    closeDrawer()
    refetch()
  }
}

const confirmDelete = async (deduction: Deduction) => {
  if (!confirm(`Are you sure you want to delete "${deduction.name}"?`)) {
    return
  }

  try {
    await deleteDeduction.mutateAsync(deduction.uuid)
    showNotification('Deduction deleted successfully', 'success')
  } catch (error: any) {
    showNotification(error.response?.data?.message || 'Failed to delete deduction', 'error')
  }
}
</script>

<style scoped>
.deductions-table {
  border-radius: 8px;
  overflow: hidden;
}

.deductions-table :deep(.v-data-table__thead th) {
  background-color: rgba(0, 0, 0, 0.03);
  font-weight: 600;
  font-size: 0.875rem;
  padding: 14px 16px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: rgba(0, 0, 0, 0.87);
}

.deductions-table :deep(.v-data-table__tbody td) {
  padding: 14px 16px;
  font-size: 0.875rem;
}

.deductions-table :deep(.v-data-table__tbody tr:hover) {
  background-color: rgba(25, 118, 210, 0.04);
}

.action-btn {
  opacity: 0.7;
}

.action-btn:hover {
  opacity: 1;
}
</style>
