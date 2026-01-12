<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center justify-space-between flex-wrap w-100">
              <h1 class="text-h6 font-weight-bold mb-0">Contribution Management</h1>
              <v-btn
                color="primary"
                size="small"
                prepend-icon="mdi-plus"
                @click="openDrawer"
              >
                Add Contribution
              </v-btn>
            </div>
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-4">
            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
              <p class="mt-3 text-body-2 text-medium-emphasis">Loading contributions...</p>
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
                  <div class="font-weight-medium">Failed to load contributions</div>
                  <div class="text-caption">{{ error.message }}</div>
                </div>
                <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
              </div>
            </v-alert>

            <!-- Contributions Table -->
            <v-data-table
              v-else
              :items="contributions"
              :headers="headers"
              :loading="isLoading"
              density="compact"
              item-key="uuid"
              class="contributions-table"
              :items-per-page="15"
              :items-per-page-options="[10, 15, 25, 50]"
              :hide-no-data="true"
              :no-data-text="''"
              :row-height="40"
            >
              <template v-slot:[`body.append`]>
                <tr v-if="contributions.length === 0">
                  <td :colspan="headers.length" class="text-center py-8">
                    <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-account-cash</v-icon>
                    <p class="text-subtitle-1 font-weight-medium mb-1">No contributions yet</p>
                    <p class="text-body-2 text-medium-emphasis mb-3">Start by adding a contribution</p>
                    <v-btn
                      color="primary"
                      size="small"
                      prepend-icon="mdi-plus"
                      @click="openDrawer"
                    >
                      Add Contribution
                    </v-btn>
                  </td>
                </tr>
              </template>

              <template v-slot:[`item.employee_share`]="{ item }">
                {{ item.employee_share }}%
              </template>

              <template v-slot:[`item.employer_share`]="{ item }">
                {{ item.employer_share }}%
              </template>

              <template v-slot:[`item.created_at`]="{ item }">
                {{ formatDate(item.created_at) }}
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
                      @click="editContribution(item)"
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

    <!-- Contribution Form Drawer -->
    <v-navigation-drawer
      :model-value="drawer"
      @update:model-value="drawer = $event"
      location="right"
      temporary
      width="500"
      class="drawer contribution-drawer"
    >
      <v-card class="h-100 d-flex flex-column" flat>
        <v-card-title class="employee-drawer-header flex-shrink-0 py-3 px-5">
          <div class="d-flex align-center w-100">
            <v-avatar color="primary" size="40" class="me-3">
              <v-icon color="white">{{ editingContribution ? 'mdi-pencil' : 'mdi-plus' }}</v-icon>
            </v-avatar>
            <div class="flex-grow-1">
              <div class="text-h6 font-weight-bold">{{ editingContribution ? 'Edit Contribution' : 'Add Contribution' }}</div>
              <div class="text-caption text-medium-emphasis mt-1">
                {{ editingContribution ? 'Update contribution information' : 'Add a new contribution to the system' }}
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
            <ContributionForm
              ref="contributionFormRef"
              :contribution="editingContribution"
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
              :prepend-icon="editingContribution ? 'mdi-content-save' : 'mdi-check'"
            >
              {{ editingContribution ? 'Update Contribution' : 'Create Contribution' }}
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
          Are you sure you want to delete this contribution? This action cannot be undone.
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
import { ref, computed } from 'vue'
import { useContributions, useDeleteContribution } from '@/composables/useContributions'
import type { Contribution } from '@/types/contribution'
import ContributionForm from './ContributionForm.vue'
import { useNotification } from '@/composables/useNotification'

const page = ref(1)
const drawer = ref(false)
const editingContribution = ref<Contribution | null>(null)
const formRef = ref()
const contributionFormRef = ref<InstanceType<typeof ContributionForm> | null>(null)
const deleteDialog = ref(false)
const contributionToDelete = ref<Contribution | null>(null)

const { data, isLoading, error, refetch } = useContributions(page)
const { showNotification } = useNotification()
const deleteContribution = useDeleteContribution()

const contributions = computed(() => data.value?.data || [])
const meta = computed(() => data.value?.meta || {
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: null,
  to: null,
})

const isSubmitting = computed(() => contributionFormRef.value?.isSubmitting || false)
const isValid = computed(() => contributionFormRef.value?.isValid || false)

const headers = [
  { title: 'Name', key: 'name', sortable: true },
  { title: 'Employee Share (%)', key: 'employee_share', sortable: true, align: 'end' as const },
  { title: 'Employer Share (%)', key: 'employer_share', sortable: true, align: 'end' as const },
  { title: 'Created At', key: 'created_at', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
]

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const openDrawer = () => {
  editingContribution.value = null
  drawer.value = true
}

const closeDrawer = () => {
  drawer.value = false
  editingContribution.value = null
  contributionFormRef.value?.resetForm()
}

const editContribution = (contribution: Contribution) => {
  editingContribution.value = contribution
  drawer.value = true
}

const handleFormSubmit = async () => {
  if (contributionFormRef.value) {
    await contributionFormRef.value.handleSubmit()
    closeDrawer()
    refetch()
  }
}

const confirmDelete = (contribution: Contribution) => {
  contributionToDelete.value = contribution
  deleteDialog.value = true
}

const deleteMutation = deleteContribution

const handleDelete = async () => {
  if (!contributionToDelete.value) return
  try {
    await deleteContribution.mutateAsync(contributionToDelete.value.uuid)
    showNotification('Contribution deleted successfully', 'success')
    deleteDialog.value = false
    contributionToDelete.value = null
    refetch()
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    const message = err?.response?.data?.message || 'Failed to delete contribution'
    showNotification(message, 'error')
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

.action-btn {
  color: rgba(0, 0, 0, 0.6);
}

.action-btn:hover {
  color: rgba(0, 0, 0, 0.87);
}
</style>
