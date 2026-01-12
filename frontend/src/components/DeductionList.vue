<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex align-center justify-space-between">
            <span>Deductions</span>
            <v-btn color="primary" prepend-icon="mdi-plus" @click="openDrawer">
              Add Deduction
            </v-btn>
          </v-card-title>

          <v-card-text>
            <v-data-table
              v-model:page="page"
              :headers="headers"
              :items="deductionsData"
              :loading="isLoading"
              :items-per-page="10"
              :server-items-length="meta.total"
              class="deductions-table"
            >
              <template #item.type="{ item }">
                <v-chip :color="item.type === 'fixed' ? 'primary' : 'success'" size="small">
                  {{ item.type }}
                </v-chip>
              </template>

              <template #item.actions="{ item }">
                <v-menu location="bottom end">
                  <template #activator="{ props }">
                    <v-btn icon="mdi-dots-vertical" variant="text" size="small" v-bind="props" />
                  </template>
                  <v-list>
                    <v-list-item prepend-icon="mdi-pencil" @click="editDeduction(item)">
                      Edit
                    </v-list-item>
                    <v-list-item prepend-icon="mdi-delete" @click="confirmDelete(item)">
                      Delete
                    </v-list-item>
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
      class="deduction-drawer"
    >
      <div class="deduction-drawer-header">
        <v-card-title class="d-flex align-center justify-space-between">
          <span>{{ editingDeduction ? 'Edit Deduction' : 'Add Deduction' }}</span>
          <v-btn icon="mdi-close" variant="text" @click="closeDrawer" />
        </v-card-title>
      </div>

      <v-card-text class="pa-5">
        <DeductionForm
          v-if="drawer"
          :deduction="editingDeduction"
          @submit="handleSubmit"
          @cancel="closeDrawer"
        />
      </v-card-text>
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

const { data, isLoading } = useDeductions(page)
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

const headers = [
  { title: 'Name', key: 'name', align: 'start' as const },
  { title: 'Type', key: 'type', align: 'start' as const },
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

const handleSubmit = () => {
  closeDrawer()
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
.deduction-drawer-header {
  height: 60px;
  display: flex;
  align-items: center;
  border-bottom: 1px solid rgba(0, 0, 0, 0.12);
}

.deduction-drawer-header :deep(.v-card-title) {
  padding: 0 16px;
  height: 100%;
  flex: 1;
}

.deductions-table :deep(.v-data-table__wrapper) {
  border-radius: 4px;
}
</style>
