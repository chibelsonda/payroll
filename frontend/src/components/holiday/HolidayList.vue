<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center justify-space-between flex-wrap w-100">
              <h1 class="text-h6 font-weight-bold mb-0">Holidays</h1>
              <v-btn color="primary" size="small" prepend-icon="mdi-plus" @click="openDrawer">
                Add Holiday
              </v-btn>
            </div>
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-0">
            <div v-if="isLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
              <p class="mt-3 text-body-2 text-medium-emphasis">Loading holidays...</p>
            </div>

            <v-alert v-else-if="error" type="error" variant="tonal" class="ma-4" rounded="lg" density="compact">
              <div class="d-flex align-center">
                <v-icon class="me-3">mdi-alert-circle</v-icon>
                <div class="flex-grow-1">
                  <div class="font-weight-medium">Failed to load holidays</div>
                  <div class="text-caption">{{ error.message }}</div>
                </div>
                <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
              </div>
            </v-alert>

            <v-data-table
              v-else
              :items="holidays"
              :headers="headers"
              :loading="isLoading"
              density="compact"
              item-key="uuid"
              class="holidays-table"
              :items-per-page="15"
              :items-per-page-options="[10, 15, 25, 50]"
            >
              <template v-slot:[`body.append`]>
                <tr v-if="holidaysList.length === 0">
                  <td :colspan="headers.length" class="text-center py-8">
                    <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-calendar-star</v-icon>
                    <p class="text-subtitle-1 font-weight-medium mb-1">No holidays yet</p>
                    <p class="text-body-2 text-medium-emphasis mb-3">Get started by adding a holiday</p>
                    <v-btn color="primary" size="small" prepend-icon="mdi-plus" @click="openDrawer">
                      Add Holiday
                    </v-btn>
                  </td>
                </tr>
              </template>

              <template v-slot:[`item.date`]="{ item }">
                <span class="text-body-2">{{ formatDate(item.date) }}</span>
              </template>

              <template v-slot:[`item.type`]="{ item }">
                <v-chip size="small" variant="tonal" :color="getTypeColor(item.type)">
                  {{ item.type }}
                </v-chip>
              </template>

              <template v-slot:[`item.is_recurring`]="{ item }">
                <v-icon v-if="item.is_recurring" color="success" size="small">mdi-check-circle</v-icon>
                <v-icon v-else color="grey" size="small">mdi-circle-outline</v-icon>
              </template>

              <template v-slot:[`item.actions`]="{ item }">
                <v-btn icon="mdi-pencil" size="small" variant="text" @click="editHoliday(item)"></v-btn>
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
            <span class="text-h6">{{ editingHoliday ? 'Edit Holiday' : 'Add Holiday' }}</span>
            <v-btn icon="mdi-close" variant="text" size="small" @click="closeDrawer"></v-btn>
          </div>
        </v-card-title>

        <v-divider class="flex-shrink-0"></v-divider>

        <v-form ref="formRef" @submit.prevent="handleFormSubmit" class="d-flex flex-column flex-grow-1" style="min-height: 0;">
          <v-card-text class="flex-grow-1 overflow-y-auto pa-6" style="min-height: 0;">
            <HolidayForm ref="holidayFormRef" :holiday="editingHoliday" />
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
              :prepend-icon="editingHoliday ? 'mdi-content-save' : 'mdi-check'"
            >
              {{ editingHoliday ? 'Update Holiday' : 'Create Holiday' }}
            </v-btn>
          </v-card-actions>
        </v-form>
      </v-card>
    </v-navigation-drawer>

    <!-- Delete Confirmation Dialog -->
    <ConfirmDialog
      v-model="deleteDialog"
      title="Delete Holiday"
      message="Are you sure you want to delete this holiday? This action cannot be undone."
      type="danger"
      :loading="isDeleting"
      @confirm="handleDelete"
      @cancel="deleteDialog = false"
    />
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useHolidays } from '@/composables/holiday/useHolidays'
import type { Holiday } from '@/types/holiday'
import HolidayForm from './HolidayForm.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'

const drawer = ref(false)
const editingHoliday = ref<Holiday | null>(null)
const formRef = ref()
const holidayFormRef = ref<InstanceType<typeof HolidayForm> | null>(null)
const deleteDialog = ref(false)
const holidayToDelete = ref<Holiday | null>(null)

const { holidays, isLoading, error, refetch, createHoliday, updateHoliday, deleteHoliday, isCreating, isUpdating, isDeleting } = useHolidays()

const holidaysList = computed(() => holidays.value || [])

const headers = [
  { title: 'Name', key: 'name', sortable: true },
  { title: 'Date', key: 'date', sortable: true },
  { title: 'Type', key: 'type', sortable: true },
  { title: 'Recurring', key: 'is_recurring', sortable: true },
  { title: 'Description', key: 'description', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
]

const isSubmitting = computed(() => isCreating.value || isUpdating.value)
const isValid = computed(() => holidayFormRef.value?.isValid || false)

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const getTypeColor = (type: string): string => {
  const colors: Record<string, string> = {
    regular: 'primary',
    special: 'warning',
    local: 'info',
  }
  return colors[type] || 'grey'
}

const openDrawer = () => {
  editingHoliday.value = null
  drawer.value = true
}

const closeDrawer = () => {
  drawer.value = false
  editingHoliday.value = null
  holidayFormRef.value?.resetForm()
}

const editHoliday = (holiday: Holiday) => {
  editingHoliday.value = holiday
  drawer.value = true
}

const confirmDelete = (holiday: Holiday) => {
  holidayToDelete.value = holiday
  deleteDialog.value = true
}

const handleFormSubmit = async () => {
  if (!holidayFormRef.value) return

  const values = holidayFormRef.value.values

  try {
    if (editingHoliday.value) {
      await updateHoliday({
        uuid: editingHoliday.value.uuid,
        data: {
          name: values.name,
          date: values.date,
          type: values.type,
          is_recurring: values.is_recurring,
          description: values.description,
        },
      })
    } else {
      await createHoliday({
        name: values.name,
        date: values.date,
        type: values.type,
        is_recurring: values.is_recurring,
        description: values.description,
      })
    }
    closeDrawer()
  } catch (error) {
    console.error('Error saving holiday:', error)
  }
}

const handleDelete = async () => {
  if (!holidayToDelete.value?.uuid) return

  try {
    await deleteHoliday(holidayToDelete.value.uuid)
    deleteDialog.value = false
    holidayToDelete.value = null
  } catch (error) {
    console.error('Error deleting holiday:', error)
  }
}
</script>
