<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title>
            <h2>Employees Management</h2>
            <v-spacer></v-spacer>
            <v-btn color="primary" @click="showCreateDialog = true">
              <v-icon left>mdi-plus</v-icon>
              Add Employee
            </v-btn>
          </v-card-title>

          <v-card-text>
            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
              <p class="mt-4">Loading employees...</p>
            </div>

            <!-- Error State -->
            <v-alert v-else-if="error" type="error" class="mb-4">
              Failed to load employees: {{ error.message }}
              <template #append>
                <v-btn variant="text" @click="refetch">Retry</v-btn>
              </template>
            </v-alert>

            <!-- Employees Table -->
            <v-data-table
              v-else
              :items="employees"
              :headers="headers"
              :loading="isLoading"
              density="compact"
              item-key="uuid"
            >
              <template #item.user="{ item }">
                {{ item.user.first_name }} {{ item.user.last_name }}
              </template>

              <template #item.actions="{ item }">
                <v-btn
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  @click="editEmployee(item)"
                ></v-btn>
                <v-btn
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  color="error"
                  @click="deleteEmployee(item)"
                ></v-btn>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Create/Edit Employee Dialog -->
    <v-dialog v-model="showCreateDialog" max-width="500px">
      <v-card>
        <v-card-title>
          {{ editingEmployee ? 'Edit Employee' : 'Create Employee' }}
        </v-card-title>
        <v-card-text>
          <v-form ref="form" v-model="formValid">
            <v-text-field
              v-model="employeeForm.first_name"
              label="First Name"
              :rules="[v => !!v || 'First name is required']"
              required
              density="compact"
              variant="outlined"
            ></v-text-field>
            <v-text-field
              v-model="employeeForm.last_name"
              label="Last Name"
              :rules="[v => !!v || 'Last name is required']"
              required
              density="compact"
              variant="outlined"
            ></v-text-field>
            <v-text-field
              v-model="employeeForm.email"
              label="Email"
              type="email"
              :rules="[v => !!v || 'Email is required']"
              required
              density="compact"
              variant="outlined"
            ></v-text-field>
            <v-text-field
              v-model="employeeForm.employee_id"
              label="Employee ID"
              :rules="[v => !!v || 'Employee ID is required']"
              required
              density="compact"
              variant="outlined"
            ></v-text-field>
            <v-text-field
              v-if="!editingEmployee"
              v-model="employeeForm.password"
              label="Password"
              type="password"
              :rules="[v => !!v || 'Password is required']"
              required
              density="compact"
              variant="outlined"
            ></v-text-field>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="closeDialog">Cancel</v-btn>
          <v-btn
            color="primary"
            :loading="createMutation.isPending || updateMutation.isPending"
            @click="saveEmployee"
          >
            {{ editingEmployee ? 'Update' : 'Create' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="showDeleteDialog" max-width="400px">
      <v-card>
        <v-card-title>Confirm Delete</v-card-title>
        <v-card-text>
          Are you sure you want to delete employee "{{ selectedEmployee?.user?.first_name }} {{ selectedEmployee?.user?.last_name }}"?
          This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="showDeleteDialog = false">Cancel</v-btn>
          <v-btn
            color="error"
            :loading="deleteMutation.isPending"
            @click="confirmDelete"
          >
            Delete
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import {
  useEmployees,
  useCreateEmployee,
  useUpdateEmployee,
  useDeleteEmployee
} from '@/composables'
import { useRegister } from '@/composables/useAuth'
import type { Employee } from '@/types/auth'

// Reactive state
const showCreateDialog = ref(false)
const showDeleteDialog = ref(false)
const editingEmployee = ref<Employee | null>(null)
const selectedEmployee = ref<Employee | null>(null)
const formValid = ref(false)

// Form data
const employeeForm = reactive({
  first_name: '',
  last_name: '',
  email: '',
  employee_id: '',
  password: ''
})

// Vue Query hooks
const { data: employeesData, isLoading, error, refetch } = useEmployees()
const createMutation = useCreateEmployee()
const updateMutation = useUpdateEmployee()
const deleteMutation = useDeleteEmployee()
const registerMutation = useRegister()

// Computed properties
const employees = computed(() => employeesData.value?.data || [])

const headers = [
  { title: 'Employee ID', key: 'employee_id' },
  { title: 'Name', key: 'user' },
  { title: 'Email', key: 'user.email' },
  { title: 'Actions', key: 'actions', sortable: false }
]

// Methods
const resetForm = () => {
  Object.assign(employeeForm, {
    first_name: '',
    last_name: '',
    email: '',
    employee_id: '',
    password: ''
  })
  editingEmployee.value = null
}

const closeDialog = () => {
  showCreateDialog.value = false
  resetForm()
}

const editEmployee = (employee: Employee) => {
  editingEmployee.value = employee
  employeeForm.first_name = employee.user.first_name
  employeeForm.last_name = employee.user.last_name
  employeeForm.email = employee.user.email
  employeeForm.employee_id = employee.employee_id
  showCreateDialog.value = true
}

const saveEmployee = async () => {
  if (!formValid.value) return

  try {
    const formData = {
      first_name: employeeForm.first_name,
      last_name: employeeForm.last_name,
      email: employeeForm.email,
      employee_id: employeeForm.employee_id,
      ...(editingEmployee.value ? {} : { password: employeeForm.password })
    }

    if (editingEmployee.value) {
      // For updates, we need to update the user data separately
      // This is a simplified example - in a real app you'd have a dedicated endpoint
      await updateMutation.mutateAsync({
        uuid: editingEmployee.value.uuid,
        data: { employee_id: employeeForm.employee_id }
      })
    } else {
      await createMutation.mutateAsync(formData)
    }

    closeDialog()
  } catch (error) {
    console.error('Failed to save employee:', error)
  }
}

const deleteEmployee = (employee: Employee) => {
  selectedEmployee.value = employee
  showDeleteDialog.value = true
}

const confirmDelete = async () => {
  if (!selectedEmployee.value) return

  try {
    await deleteMutation.mutateAsync(selectedEmployee.value.uuid)
    showDeleteDialog.value = false
    selectedEmployee.value = null
  } catch (error) {
    console.error('Failed to delete employee:', error)
  }
}
</script>
