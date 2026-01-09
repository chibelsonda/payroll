<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <!-- Main Content Card -->
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center justify-space-between flex-wrap w-100">
              <h1 class="text-h6 font-weight-bold mb-0">Employees Management</h1>
              <v-btn
                color="primary"
                size="small"
                prepend-icon="mdi-plus"
                @click="openEmployeeDrawer"
              >
              Add Employee
            </v-btn>
            </div>
          </v-card-title>

          <v-divider></v-divider>

          <v-card-text class="pa-0">
            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
              <p class="mt-3 text-body-2 text-medium-emphasis">Loading employees...</p>
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
                  <div class="font-weight-medium">Failed to load employees</div>
                  <div class="text-caption">{{ error.message }}</div>
                </div>
                <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
              </div>
            </v-alert>

            <!-- Employees Table -->
            <v-data-table
              v-else
              :items="filteredEmployees"
              :headers="headers"
              :loading="isLoading"
              density="compact"
              item-key="uuid"
              class="employees-table"
              :items-per-page="15"
              :items-per-page-options="[10, 15, 25, 50]"
              :hide-no-data="true"
              :no-data-text="''"
            >
              <!-- Filter row in body -->
              <template v-slot:[`body.prepend`]>
                <tr class="filter-row">
                  <td>
                    <v-text-field
                      v-model="filters.employeeId"
                      density="compact"
                      variant="outlined"
                      hide-details
                      clearable
                      placeholder="Filter..."
                      class="filter-input"
                    ></v-text-field>
                  </td>
                  <td>
                    <v-text-field
                      v-model="filters.name"
                      density="compact"
                      variant="outlined"
                      hide-details
                      clearable
                      placeholder="Filter..."
                      class="filter-input"
                    ></v-text-field>
                  </td>
                  <td>
                    <v-text-field
                      v-model="filters.email"
                      density="compact"
                      variant="outlined"
                      hide-details
                      clearable
                      placeholder="Filter..."
                      class="filter-input"
                    ></v-text-field>
                  </td>
                  <td></td>
                </tr>
              </template>

              <!-- Empty state in table body -->
              <template v-slot:[`body.append`]>
                <tr v-if="filteredEmployees.length === 0">
                  <td :colspan="headers.length" class="text-center py-8">
                    <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-account-group-outline</v-icon>
                    <p class="text-subtitle-1 font-weight-medium mb-1">
                      {{ hasActiveFilters ? 'No employees found' : 'No employees yet' }}
                    </p>
                    <p class="text-body-2 text-medium-emphasis mb-3">
                      {{ hasActiveFilters ? 'Try adjusting your filters' : 'Get started by adding your first employee' }}
                    </p>
                <v-btn
                      v-if="!hasActiveFilters"
                      color="primary"
                      size="small"
                      prepend-icon="mdi-plus"
                      @click="openEmployeeDrawer"
                    >
                      Add Employee
                    </v-btn>
                  </td>
                </tr>
              </template>

              <template v-slot:[`item.user`]="{ item }">
                <div class="d-flex align-center">
                  <v-avatar size="32" color="primary" class="me-3">
                    <span class="text-white text-caption font-weight-bold">
                      {{ getInitials(item.user) }}
                    </span>
                  </v-avatar>
                  <div>
                    <div class="font-weight-medium">{{ item.user?.first_name }} {{ item.user?.last_name }}</div>
                  </div>
                </div>
              </template>

              <template v-slot:[`item.email`]="{ item }">
                <div class="d-flex align-center">
                  <v-icon size="16" class="me-2 text-medium-emphasis">mdi-email-outline</v-icon>
                  <span class="text-body-2">{{ item.user?.email }}</span>
                </div>
              </template>

              <template v-slot:[`item.employee_id`]="{ item }">
                <v-chip
                  size="small"
                  variant="outlined"
                  color="primary"
                >
                  {{ item.employee_id }}
                </v-chip>
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
                      @click="editEmployee(item)"
                    ></v-list-item>
                    <v-list-item
                      prepend-icon="mdi-delete"
                      title="Delete"
                      class="text-error"
                  @click="deleteEmployee(item)"
                    ></v-list-item>
                  </v-list>
                </v-menu>
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
      class="employee-drawer"
    >
      <v-card class="d-flex flex-column employee-drawer-card" style="height: 100%; overflow: hidden;" elevation="0">
        <!-- Enhanced Header -->
        <v-card-title class="employee-drawer-header flex-shrink-0 py-2 px-5">
          <div class="d-flex align-center w-100">
            <v-avatar
              color="primary"
              size="40"
              class="me-3"
            >
              <v-icon color="white">{{ editingEmployee ? 'mdi-pencil' : 'mdi-account-plus' }}</v-icon>
            </v-avatar>
            <div class="flex-grow-1">
              <div class="text-h6 font-weight-bold">{{ editingEmployee ? 'Edit Employee' : 'Create Employee' }}</div>
              <div class="text-caption text-medium-emphasis mt-1">
                {{ editingEmployee ? 'Update employee information' : 'Add a new employee to the system' }}
              </div>
            </div>
            <v-btn
              icon="mdi-close"
              variant="text"
              size="small"
              @click="closeDialog"
              class="ml-2"
            ></v-btn>
          </div>
        </v-card-title>

        <v-divider class="flex-shrink-0"></v-divider>

        <v-form ref="formRef" @submit.prevent="handleButtonClick" class="d-flex flex-column flex-grow-1" style="min-height: 0;">
          <v-card-text class="flex-grow-1 overflow-y-auto pa-6" style="min-height: 0;">
            <!-- Personal Information Section -->
            <div class="mb-6">
              <div class="text-subtitle-2 font-weight-medium mb-4 text-primary">Personal Information</div>

              <!-- First Name -->
              <div class="mb-4">
                <div class="text-body-2 mb-1 font-weight-medium">First Name</div>
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
                  class="employee-form-field"
            ></v-text-field>
              </div>

              <!-- Last Name -->
              <div class="mb-4">
                <div class="text-body-2 mb-1 font-weight-medium">Last Name</div>
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
                  class="employee-form-field"
            ></v-text-field>
              </div>
            </div>

            <v-divider class="my-4"></v-divider>

            <!-- Account Information Section -->
            <div class="mb-6">
              <div class="text-subtitle-2 font-weight-medium mb-4 text-primary">Account Information</div>

              <!-- Email -->
              <div class="mb-4">
                <div class="text-body-2 mb-1 font-weight-medium">Email Address</div>
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
                  class="employee-form-field"
            ></v-text-field>
              </div>

              <!-- Password (Create mode only) -->
              <div v-if="!editingEmployee" class="mb-4">
                <div class="text-body-2 mb-1 font-weight-medium">Password</div>
            <v-text-field
                  v-model="password"
                  placeholder="Enter password (min. 8 characters)"
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
                  class="employee-form-field"
                  @click:append-inner="showPassword = !showPassword"
            ></v-text-field>
              </div>
            </div>

            <v-divider class="my-4"></v-divider>

            <!-- Employee Details Section -->
            <div class="mb-4">
              <div class="text-subtitle-2 font-weight-medium mb-4 text-primary">Employee Details</div>

              <!-- Employee ID -->
              <div class="mb-4">
                <div class="text-body-2 mb-1 font-weight-medium">Employee ID</div>
            <v-text-field
                  v-model="employeeId"
                  placeholder="Enter unique employee ID"
                  prepend-inner-icon="mdi-identifier"
                  :error-messages="employeeIdError"
                  :error="hasEmployeeIdError"
              required
              density="compact"
              variant="outlined"
                  hide-details="auto"
                  class="employee-form-field"
            ></v-text-field>
              </div>
            </div>
        </v-card-text>

          <v-divider class="flex-shrink-0"></v-divider>

          <v-card-actions class="pa-4 flex-shrink-0 bg-grey-lighten-5">
            <v-btn
              type="button"
              variant="outlined"
              @click="closeDialog"
              class="flex-grow-1"
              size="small"
            >
              Cancel
            </v-btn>
            <v-spacer class="mx-2"></v-spacer>
          <v-btn
              type="button"
            color="primary"
              variant="flat"
              size="small"
              :loading="isSaving || isSubmitting"
              @click="handleButtonClick"
              class="flex-grow-1"
              :prepend-icon="editingEmployee ? 'mdi-content-save' : 'mdi-check'"
          >
              {{ editingEmployee ? 'Update Employee' : 'Create Employee' }}
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
// Column filters
const filters = ref({
  employeeId: '',
  name: '',
  email: '',
})

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

// Watch editingEmployee to ensure dummy password is set for edit mode validation.
// This is a safety net in case editingEmployee is set outside of editEmployee().
// The password field is hidden in edit mode (v-if="!editingEmployee"), but the
// form schema (createEmployeeSchema) still requires it for validation.
// We set a dummy password that passes validation, but it won't be sent to the API.
watch(editingEmployee, (newValue: Employee | null) => {
  if (newValue) {
    // Set dummy password if field is empty or doesn't meet minimum length
    // (though in practice, the field should always be empty in edit mode)
    const currentPassword = passwordField.value.value as string
    if (!currentPassword || currentPassword.length < 8) {
      setFieldValue('password', 'dummy_password_for_edit_validation')
    }
  }
}, { immediate: false })

// Wrapper for handleSubmit that ensures all fields (including password) are validated
// before the callback executes. VeeValidate's handleSubmit validates ALL fields
// simultaneously when the form is submitted. If any field fails validation,
// the callback won't be called.
const createFormHandler = (callback: (values: CreateEmployeeFormData) => Promise<void> | void) => {
  return baseHandleSubmit(async (values: CreateEmployeeFormData) => {
    // At this point, all fields have passed validation including password.
    // Note: In edit mode, the callback should exclude the dummy password from
    // the API request (see onSubmit function where password is omitted).
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

// Check if any filters are active
const hasActiveFilters = computed(() => {
  return !!(filters.value.employeeId || filters.value.name || filters.value.email)
})

// Filter employees based on column filters
const filteredEmployees = computed(() => {
  let result = employees.value

  // Filter by Employee ID
  if (filters.value.employeeId) {
    const query = filters.value.employeeId.toLowerCase()
    result = result.filter((emp) =>
      emp.employee_id?.toLowerCase().includes(query)
    )
  }

  // Filter by Name
  if (filters.value.name) {
    const query = filters.value.name.toLowerCase()
    result = result.filter((emp) => {
      const name = `${emp.user?.first_name || ''} ${emp.user?.last_name || ''}`.toLowerCase()
      return name.includes(query)
    })
  }

  // Filter by Email
  if (filters.value.email) {
    const query = filters.value.email.toLowerCase()
    result = result.filter((emp) =>
      emp.user?.email?.toLowerCase().includes(query)
    )
  }

  return result
})

const headers = [
  { title: 'Employee ID', key: 'employee_id', sortable: true },
  { title: 'Name', key: 'user', sortable: true },
  { title: 'Email', key: 'email', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const }
]

// Helper function to get user initials
const getInitials = (user: { first_name?: string; last_name?: string } | undefined): string => {
  if (!user) return '??'
  const first = user.first_name?.charAt(0).toUpperCase() || ''
  const last = user.last_name?.charAt(0).toUpperCase() || ''
  return `${first}${last}` || '??'
}

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

<style scoped>
.employees-table :deep(.v-data-table__thead) {
  background-color: rgba(0, 0, 0, 0.02);
}

.employees-table :deep(.v-data-table__thead th) {
  font-weight: 600;
  color: rgba(0, 0, 0, 0.87);
}

.employees-table :deep(.v-data-table__tbody tr:hover) {
  background-color: rgba(25, 118, 210, 0.04);
}

.action-btn {
  transition: transform 0.2s;
}

.action-btn:hover {
  transform: scale(1.1);
}

/* Employee Drawer Styles */
.employee-drawer {
  top: 0 !important;
  height: 100vh !important;
  z-index: 2000 !important;
}

.employee-drawer :deep(.v-navigation-drawer__content) {
  height: 100% !important;
  overflow: hidden;
}

.employee-drawer-card {
  background: #ffffff;
}

.employee-drawer-header {
  background: linear-gradient(135deg, rgba(25, 118, 210, 0.08) 0%, rgba(25, 118, 210, 0.02) 100%);
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.employee-form-field :deep(.v-field) {
  border-radius: 4px;
}

.employee-form-field :deep(.v-field__input) {
  padding-top: 4px;
  padding-bottom: 4px;
}

.filter-row {
  background-color: rgba(0, 0, 0, 0.02);
  display: table-row !important;
}

.filter-row td {
  padding: 6px 16px !important;
  vertical-align: middle;
  display: table-cell !important;
}

.employees-table :deep(.v-data-table__tbody) {
  display: table-row-group !important;
}

.filter-input {
  margin: 0;
}

.filter-input :deep(.v-field) {
  font-size: 0.875rem;
  min-height: 28px;
  max-height: 28px;
}

.filter-input :deep(.v-field__input) {
  min-height: 28px;
  padding: 2px 8px;
}

.filter-input :deep(.v-input__details) {
  display: none;
}
</style>
