<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center justify-space-between flex-wrap w-100">
              <h1 class="text-h6 font-weight-bold mb-0">Shifts</h1>
              <v-btn color="primary" size="small" prepend-icon="mdi-plus" @click="openDrawer">
                Add Shift
              </v-btn>
            </div>
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-0">
            <div v-if="isLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
              <p class="mt-3 text-body-2 text-medium-emphasis">Loading shifts...</p>
            </div>

            <v-alert v-else-if="error" type="error" variant="tonal" class="ma-4" rounded="lg" density="compact">
              <div class="d-flex align-center">
                <v-icon class="me-3">mdi-alert-circle</v-icon>
                <div class="flex-grow-1">
                  <div class="font-weight-medium">Failed to load shifts</div>
                  <div class="text-caption">{{ error.message }}</div>
                </div>
                <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
              </div>
            </v-alert>

            <v-data-table
              v-else
              :items="shiftsList"
              :headers="headers"
              :loading="isLoading"
              density="compact"
              item-key="uuid"
              class="shifts-table"
              :items-per-page="15"
              :items-per-page-options="[10, 15, 25, 50]"
            >
              <template v-slot:[`body.append`]>
                <tr v-if="shiftsList.length === 0">
                  <td :colspan="headers.length" class="text-center py-8">
                    <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-clock-outline</v-icon>
                    <p class="text-subtitle-1 font-weight-medium mb-1">No shifts yet</p>
                    <p class="text-body-2 text-medium-emphasis mb-3">Get started by adding a shift</p>
                    <v-btn color="primary" size="small" prepend-icon="mdi-plus" @click="openDrawer">
                      Add Shift
                    </v-btn>
                  </td>
                </tr>
              </template>

              <template v-slot:[`item.start_time`]="{ item }">
                <span class="text-body-2">{{ formatTime(item.start_time) }}</span>
              </template>

              <template v-slot:[`item.end_time`]="{ item }">
                <span class="text-body-2">{{ formatTime(item.end_time) }}</span>
              </template>

              <template v-slot:[`item.break_duration_minutes`]="{ item }">
                <span class="text-body-2">{{ item.break_duration_minutes }} min</span>
              </template>

              <template v-slot:[`item.is_active`]="{ item }">
                <v-chip size="small" :color="item.is_active ? 'success' : 'grey'" variant="tonal">
                  {{ item.is_active ? 'Active' : 'Inactive' }}
                </v-chip>
              </template>

              <template v-slot:[`item.actions`]="{ item }">
                <v-btn icon="mdi-pencil" size="small" variant="text" @click="editShift(item)"></v-btn>
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
            <span class="text-h6">{{ editingShift ? 'Edit Shift' : 'Add Shift' }}</span>
            <v-btn icon="mdi-close" variant="text" size="small" @click="closeDrawer"></v-btn>
          </div>
        </v-card-title>

        <v-divider class="flex-shrink-0"></v-divider>

        <v-form ref="formRef" @submit.prevent="handleFormSubmit" class="d-flex flex-column flex-grow-1" style="min-height: 0;">
          <v-card-text class="flex-grow-1 overflow-y-auto pa-6" style="min-height: 0;">
            <ShiftForm ref="shiftFormRef" :shift="editingShift" />
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
              :prepend-icon="editingShift ? 'mdi-content-save' : 'mdi-check'"
            >
              {{ editingShift ? 'Update Shift' : 'Create Shift' }}
            </v-btn>
          </v-card-actions>
        </v-form>
      </v-card>
    </v-navigation-drawer>

    <!-- Delete Confirmation Dialog -->
    <ConfirmDialog
      v-model="deleteDialog"
      title="Delete Shift"
      message="Are you sure you want to delete this shift? This action cannot be undone."
      type="danger"
      :loading="isDeleting"
      @confirm="handleDelete"
      @cancel="deleteDialog = false"
    />
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useShifts } from '@/composables/shift/useShifts'
import type { Shift } from '@/types/shift'
import ShiftForm from './ShiftForm.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'

const drawer = ref(false)
const editingShift = ref<Shift | null>(null)
const formRef = ref()
const shiftFormRef = ref<InstanceType<typeof ShiftForm> | null>(null)
const deleteDialog = ref(false)
const shiftToDelete = ref<Shift | null>(null)

const { shifts, isLoading, error, refetch, createShift, updateShift, deleteShift, isCreating, isUpdating, isDeleting } = useShifts()

const shiftsList = computed(() => shifts.value || [])

const headers = [
  { title: 'Name', key: 'name', sortable: true },
  { title: 'Start Time', key: 'start_time', sortable: true },
  { title: 'End Time', key: 'end_time', sortable: true },
  { title: 'Break Duration', key: 'break_duration_minutes', sortable: true },
  { title: 'Status', key: 'is_active', sortable: true },
  { title: 'Description', key: 'description', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
]

const isSubmitting = computed(() => isCreating.value || isUpdating.value)
const isValid = computed(() => shiftFormRef.value?.isValid || false)

const formatTime = (time: string): string => {
  const [hours, minutes] = time.split(':')
  const hour = parseInt(hours)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const displayHour = hour % 12 || 12
  return `${displayHour}:${minutes} ${ampm}`
}

const openDrawer = () => {
  editingShift.value = null
  drawer.value = true
}

const closeDrawer = () => {
  drawer.value = false
  editingShift.value = null
  shiftFormRef.value?.resetForm()
}

const editShift = (shift: Shift) => {
  editingShift.value = shift
  drawer.value = true
}

const confirmDelete = (shift: Shift) => {
  shiftToDelete.value = shift
  deleteDialog.value = true
}

const handleFormSubmit = async () => {
  if (!shiftFormRef.value) return

  const values = shiftFormRef.value.values

  try {
    if (editingShift.value) {
      await updateShift({
        uuid: editingShift.value.uuid,
        data: {
          name: values.name,
          start_time: values.start_time,
          end_time: values.end_time,
          break_duration_minutes: values.break_duration_minutes,
          is_active: values.is_active,
          description: values.description,
        },
      })
    } else {
      await createShift({
        name: values.name,
        start_time: values.start_time,
        end_time: values.end_time,
        break_duration_minutes: values.break_duration_minutes,
        is_active: values.is_active,
        description: values.description,
      })
    }
    closeDrawer()
  } catch (error) {
    console.error('Error saving shift:', error)
  }
}

const handleDelete = async () => {
  if (!shiftToDelete.value?.uuid) return

  try {
    await deleteShift(shiftToDelete.value.uuid)
    deleteDialog.value = false
    shiftToDelete.value = null
  } catch (error) {
    console.error('Error deleting shift:', error)
  }
}
</script>
