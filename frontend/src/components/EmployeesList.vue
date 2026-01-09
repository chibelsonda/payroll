<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title>
            <h2>Employees Management</h2>
            <v-spacer></v-spacer>
            <v-btn color="primary" @click="openEmployeeDrawer">
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

    <!-- Create/Edit Employee Drawer -->
    <v-navigation-drawer
      v-model="showCreateDialog"
      location="right"
      temporary
      width="500"
    >
      <v-card class="h-100 d-flex flex-column">
        <v-card-title class="d-flex align-center">
          <span class="text-h6">{{ editingEmployee ? 'Edit Employee' : 'Create Employee' }}</span>
          <v-spacer></v-spacer>
          <v-btn
            icon="mdi-close"
            variant="text"
            size="small"
            @click="closeDialog"
          ></v-btn>
        </v-card-title>

        <v-divider></v-divider>

        <v-form ref="formRef" @submit.prevent="handleButtonClick" class="d-flex flex-column flex-grow-1">
          <v-card-text class="flex-grow-1 overflow-y-auto pa-6">
            <!-- First Name Section -->
            <div class="mb-6">
              <div class="text-body-2 mb-1">First Name</div>
              <v-text-field
                v-model="firstName"
                placeholder="Enter first name"
                prepend-inner-icon="mdi-account-outline"
                :error-messages="firstNameError"
                :error="hasFirstNameError"
                required
                density="compact"
                variant="outlined"
                hide-details="auto"
              ></v-text-field>
            </div>

            <!-- Last Name Section -->
            <div class="mb-6">
              <div class="text-body-2 mb-1">Last Name</div>
              <v-text-field
                v-model="lastName"
                placeholder="Enter last name"
                prepend-inner-icon="mdi-account-outline"
                :error-messages="lastNameError"
                :error="hasLastNameError"
                required
                density="compact"
                variant="outlined"
                hide-details="auto"
              ></v-text-field>
            </div>

            <!-- Email Section -->
            <div class="mb-6">
              <div class="text-body-2 mb-1">Email</div>
              <v-text-field
                v-model="email"
                placeholder="Email address"
                type="email"
                prepend-inner-icon="mdi-email-outline"
                :error-messages="emailError"
                :error="hasEmailError"
                required
                autocomplete="email"
                density="compact"
                variant="outlined"
                hide-details="auto"
              ></v-text-field>
            </div>

            <!-- Employee ID Section -->
            <div class="mb-6">
              <div class="text-body-2 mb-1">Employee ID</div>
              <v-text-field
                v-model="employeeId"
                placeholder="Enter employee ID"
                prepend-inner-icon="mdi-identifier"
                :error-messages="employeeIdError"
                :error="hasEmployeeIdError"
                required
                density="compact"
                variant="outlined"
                hide-details="auto"
              ></v-text-field>
            </div>

            <!-- Password Section -->
            <div v-if="!editingEmployee" class="mb-4">
              <div class="text-body-2 mb-1">Password</div>
              <v-text-field
                v-model="password"
                placeholder="Enter your password"
                :type="showPassword ? 'text' : 'password'"
                prepend-inner-icon="mdi-lock-outline"
                :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                :error-messages="passwordError"
                :error="hasPasswordError"
                required
                autocomplete="new-password"
                density="compact"
                variant="outlined"
                hide-details="auto"
                @click:append-inner="showPassword = !showPassword"
              ></v-text-field>
            </div>
          </v-card-text>

          <v-divider></v-divider>

          <v-card-actions class="pa-4">
            <v-spacer></v-spacer>
            <v-btn type="button" @click="closeDialog">Cancel</v-btn>
            <v-btn
              type="button"
              color="primary"
              :loading="isSaving || isSubmitting"
              @click="handleButtonClick"
            >
              {{ editingEmployee ? 'Update' : 'Create' }}
            </v-btn>
          </v-card-actions>
        </v-form>
      </v-card>
    </v-navigation-drawer>

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
            :loading="isDeleting"
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
import { ref, computed, watch } from 'vue'
import {
  useEmployees,
  useCreateEmployee,
  useUpdateEmployee,
  useDeleteEmployee
} from '@/composables'
import { useZodForm } from '@/composables/useZodForm'
import { useNotification } from '@/composables/useNotification'
import {
  createEmployeeSchema,
  type CreateEmployeeFormData
} from '@/validation'
import type { Employee } from '@/types/auth'

// Reactive state
const showCreateDialog = ref(false)
const showDeleteDialog = ref(false)
const editingEmployee = ref<Employee | null>(null)
const selectedEmployee = ref<Employee | null>(null)
const showPassword = ref(false)
const errorMessage = ref<string | null>(null)
const formRef = ref<{ validate: () => Promise<{ valid: boolean }> } | null>(null)

const notification = useNotification()

// Initialize form with Zod validation
// Use createEmployeeSchema (requires password) so password validates simultaneously with other fields
const {
  handleSubmit: baseHandleSubmit,
  createField,
  isSubmitting,
  setServerErrors,
  clearServerErrors,
  setFieldValue,
  resetForm: resetZodForm,
} = useZodForm<CreateEmployeeFormData>(
  createEmployeeSchema,
  {
    first_name: '',
    last_name: '',
    email: '',
    employee_id: '',
    password: '',
  }
)

// Watch editingEmployee to set dummy password for edit mode validation
watch(editingEmployee, (newValue: Employee | null) => {
  if (newValue) {
    // In edit mode, set a dummy password that passes validation
    // This allows the form to validate, but we won't send password to API
    // Check current password value and set dummy if needed
    const currentPassword = passwordField.value.value as string
    if (!currentPassword || currentPassword.length < 8) {
      setFieldValue('password', 'dummy_password_for_edit_validation')
    }
  }
}, { immediate: false })

// Wrapper for handleSubmit - password is validated by schema simultaneously with other fields
// baseHandleSubmit from VeeValidate validates ALL fields (including password) before calling the callback
// The validation happens when the form is submitted, and all fields are validated at once
const createFormHandler = (callback: (values: CreateEmployeeFormData) => Promise<void> | void) => {
  return baseHandleSubmit(async (values: CreateEmployeeFormData) => {
    // VeeValidate's handleSubmit validates all fields simultaneously before this callback runs
    // If any field (including password) fails validation, this callback won't be called
    // The password field is part of createEmployeeSchema, so it will be validated with other fields
    // Schema validation happens automatically for all fields including password
    // For edit mode, we'll filter out the dummy password before sending to API
    await callback(values)
  })
}

// Create fields with validation
const firstNameField = createField('first_name')
const lastNameField = createField('last_name')
const emailField = createField('email')
const employeeIdField = createField('employee_id')
const passwordField = createField('password')

// Bind field values
const firstName = computed({
  get: () => firstNameField.value.value as string,
  set: (value: string) => firstNameField.setValue(value),
})

const lastName = computed({
  get: () => lastNameField.value.value as string,
  set: (value: string) => lastNameField.setValue(value),
})

const email = computed({
  get: () => emailField.value.value as string,
  set: (value: string) => emailField.setValue(value),
})

const employeeId = computed({
  get: () => employeeIdField.value.value as string,
  set: (value: string) => employeeIdField.setValue(value),
})

const password = computed({
  get: () => (passwordField.value.value as string) || '',
  set: (value: string) => passwordField.setValue(value),
})

// Error messages
const firstNameError = computed(() => firstNameField.errorMessage.value)
const lastNameError = computed(() => lastNameField.errorMessage.value)
const emailError = computed(() => emailField.errorMessage.value)
const employeeIdError = computed(() => employeeIdField.errorMessage.value)
const passwordError = computed(() => passwordField.errorMessage.value)

const hasFirstNameError = computed(() => !!firstNameError.value)
const hasLastNameError = computed(() => !!lastNameError.value)
const hasEmailError = computed(() => !!emailError.value)
const hasEmployeeIdError = computed(() => !!employeeIdError.value)
const hasPasswordError = computed(() => !!passwordError.value)

// Password validation is handled entirely by Zod schema
// No need for additional rules - they would conflict with Zod validation

// Vue Query hooks
const { data: employeesData, isLoading, error, refetch } = useEmployees()
const createMutation = useCreateEmployee()
const updateMutation = useUpdateEmployee()
const deleteMutation = useDeleteEmployee()

// Computed properties
const employees = computed(() => employeesData.value?.data || [])
const isSaving = computed(() => createMutation.isPending.value || updateMutation.isPending.value)
const isDeleting = computed(() => deleteMutation.isPending.value)

const headers = [
  { title: 'Employee ID', key: 'employee_id' },
  { title: 'Name', key: 'user' },
  { title: 'Email', key: 'user.email' },
  { title: 'Actions', key: 'actions', sortable: false }
]

// Methods
const resetForm = () => {
  resetZodForm()
  editingEmployee.value = null
  showPassword.value = false
  errorMessage.value = null
  clearServerErrors()
}

const openEmployeeDrawer = () => {
  editingEmployee.value = null
  resetForm()
  showCreateDialog.value = true
}

const closeDialog = () => {
  showCreateDialog.value = false
  resetForm()
}

const editEmployee = (employee: Employee) => {
  editingEmployee.value = employee
  setFieldValue('first_name', employee.user?.first_name || '')
  setFieldValue('last_name', employee.user?.last_name || '')
  setFieldValue('email', employee.user?.email || '')
  setFieldValue('employee_id', employee.employee_id || '')
  // Set a dummy password that passes validation (required by createEmployeeSchema)
  // This won't be sent to the API - it's only for form validation
  setFieldValue('password', 'dummy_password_for_edit_validation')
  showCreateDialog.value = true
}

// Handle form submission with validation
const onSubmit = createFormHandler(async (values: CreateEmployeeFormData) => {
  errorMessage.value = null
  clearServerErrors()

  try {
    if (editingEmployee.value) {
      // For updates, update user data and employee_id
      await updateMutation.mutateAsync({
        uuid: editingEmployee.value.uuid,
        data: {
          first_name: values.first_name,
          last_name: values.last_name,
          email: values.email,
          employee_id: values.employee_id
        }
      })
      notification.showSuccess('Employee updated successfully!')
    } else {
      // For creates, use employee creation endpoint (creates user + employee in one request)
      if (!values.password) {
        notification.showError('Password is required for new employees')
        return
      }

      await createMutation.mutateAsync({
        first_name: values.first_name,
        last_name: values.last_name,
        email: values.email,
        password: values.password,
        employee_id: values.employee_id
      })

      notification.showSuccess('Employee created successfully!')
    }

    closeDialog()
    refetch()
  } catch (error: unknown) {
    handleSubmitError(error)
  }
})

// Direct click handler for the button
const handleButtonClick = async (event: Event) => {
  if (event) {
    event.preventDefault()
    event.stopPropagation()
  }

  // Schema validation is handled automatically based on editingEmployee state

  // Call onSubmit - it's a function returned by baseHandleSubmit that handles validation
  // baseHandleSubmit returns a function that takes an event and validates before calling the callback
  await onSubmit(event)
}

// Helper function to handle submission errors
const handleSubmitError = (error: unknown) => {
  const err = error as { response?: { data?: { errors?: Record<string, string | string[]>; message?: string }; status?: number }; message?: string }

  if (err?.response?.data?.errors) {
    setServerErrors(err.response.data.errors)
  }

  const message =
    err?.response?.data?.message || err?.message || 'Failed to save employee. Please check the form and try again.'
  errorMessage.value = message
  notification.showError(message)
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
