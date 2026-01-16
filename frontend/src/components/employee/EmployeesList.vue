<template>
  <v-container fluid class="px-4 py-4">
    <v-row>
      <v-col cols="12">
        <!-- Main Content Card -->
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center justify-space-between flex-wrap w-100">
              <h1 class="text-h6 font-weight-bold mb-0">Employees Management</h1>
              <div class="d-flex align-center gap-3 flex-wrap">
                <v-btn
                  color="secondary"
                  size="small"
                  variant="tonal"
                  class="mr-2"
                  prepend-icon="mdi-file-upload"
                  @click="openImportDialog"
                >
                  Import CSV
                </v-btn>
                <v-btn
                  color="primary"
                  size="small"
                  prepend-icon="mdi-plus"
                  @click="openEmployeeDrawer"
                >
                  Add Employee
                </v-btn>
              </div>
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
              :row-height="36"
            >
              <!-- Filter row in body -->
              <template v-slot:[`body.prepend`]>
                <tr class="filter-row">
                  <td>
                    <v-text-field
                      v-model="filters.employeeNo"
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
                  <td>
                    <v-text-field
                      v-model="filters.company"
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
                      v-model="filters.department"
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
                      v-model="filters.position"
                      density="compact"
                      variant="outlined"
                      hide-details
                      clearable
                      placeholder="Filter..."
                      class="filter-input"
                    ></v-text-field>
                  </td>
                  <td></td>
                  <td></td>
                  <td></td>
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
                  <v-avatar size="28" color="primary" class="me-2">
                    <span class="text-white text-caption font-weight-bold">
                      {{ getInitials(item.user) }}
                    </span>
                  </v-avatar>
                  <div>
                    <div class="font-weight-medium text-body-2">{{ item.user?.first_name }} {{ item.user?.last_name }}</div>
                  </div>
                </div>
              </template>

              <template v-slot:[`item.email`]="{ item }">
                <div class="d-flex align-center">
                  <v-icon size="16" class="me-2 text-medium-emphasis">mdi-email-outline</v-icon>
                  <span class="text-body-2">{{ item.user?.email }}</span>
                </div>
              </template>

              <template v-slot:[`item.employee_no`]="{ item }">
                <v-chip
                  size="small"
                  variant="outlined"
                  color="primary"
                >
                  {{ item.employee_no }}
                </v-chip>
              </template>

              <template v-slot:[`item.company`]="{ item }">
                <span class="text-body-2">{{ item.company?.name || '-' }}</span>
              </template>

              <template v-slot:[`item.department`]="{ item }">
                <span class="text-body-2">{{ item.department?.name || '-' }}</span>
              </template>

              <template v-slot:[`item.position`]="{ item }">
                <span class="text-body-2">{{ item.position?.title || '-' }}</span>
              </template>

              <template v-slot:[`item.employment_type`]="{ item }">
                <v-chip
                  size="small"
                  :color="getEmploymentTypeColor(item.employment_type)"
                  variant="tonal"
                >
                  {{ item.employment_type || '-' }}
                </v-chip>
              </template>

              <template v-slot:[`item.status`]="{ item }">
                <v-chip
                  size="small"
                  :color="getStatusColor(item.status)"
                  variant="tonal"
                >
                  {{ item.status || 'active' }}
                </v-chip>
              </template>

              <template v-slot:[`item.hire_date`]="{ item }">
                <span class="text-body-2">{{ formatDate(item.hire_date) }}</span>
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
      class="drawer employee-drawer"
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

              <!-- Employee No -->
              <div class="mb-4">
                <div class="text-body-2 mb-1 font-weight-medium">Employee No</div>
                <v-text-field
                  v-model="employeeNo"
                  placeholder="Enter unique employee number"
                  prepend-inner-icon="mdi-identifier"
                  :error-messages="employeeNoError"
                  :error="hasEmployeeNoError"
                  required
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  class="employee-form-field"
                ></v-text-field>
              </div>

              <!-- Company -->
              <div class="mb-4">
                <div class="text-body-2 mb-1 font-weight-medium">Company</div>
                <v-select
                  v-model="companyUuid"
                  :items="companies || []"
                  item-title="name"
                  item-value="uuid"
                  placeholder="Select company"
                  prepend-inner-icon="mdi-office-building"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  class="employee-form-field v-select"
                  clearable
                ></v-select>
              </div>

              <!-- Department -->
              <div class="mb-4">
                <div class="text-body-2 mb-1 font-weight-medium">Department</div>
                <v-select
                  v-model="departmentUuid"
                  :items="departments || []"
                  item-title="name"
                  item-value="uuid"
                  placeholder="Select department"
                  prepend-inner-icon="mdi-domain"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  class="employee-form-field v-select"
                  :disabled="!companyUuid"
                  clearable
                ></v-select>
              </div>

              <!-- Position -->
              <div class="mb-4">
                <div class="text-body-2 mb-1 font-weight-medium">Position</div>
                <v-select
                  v-model="positionUuid"
                  :items="positions || []"
                  item-title="title"
                  item-value="uuid"
                  placeholder="Select position"
                  prepend-inner-icon="mdi-briefcase"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  class="employee-form-field v-select"
                  :disabled="!departmentUuid"
                  clearable
                ></v-select>
              </div>

              <!-- Employment Type -->
              <div class="mb-4">
                <div class="text-body-2 mb-1 font-weight-medium">Employment Type</div>
                <v-select
                  v-model="employmentType"
                  :items="[
                    { title: 'Regular', value: 'regular' },
                    { title: 'Contractual', value: 'contractual' },
                    { title: 'Probationary', value: 'probationary' }
                  ]"
                  placeholder="Select employment type"
                  prepend-inner-icon="mdi-account-clock"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  class="employee-form-field v-select"
                  clearable
                ></v-select>
              </div>

              <!-- Hire Date -->
              <div class="mb-4">
                <div class="text-body-2 mb-1 font-weight-medium">Hire Date</div>
                <v-text-field
                  v-model="hireDate"
                  type="date"
                  placeholder="Select hire date"
                  prepend-inner-icon="mdi-calendar"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  class="employee-form-field"
                ></v-text-field>
              </div>

              <!-- Termination Date -->
              <div class="mb-4">
                <div class="text-body-2 mb-1 font-weight-medium">Termination Date</div>
                <v-text-field
                  v-model="terminationDate"
                  type="date"
                  placeholder="Select termination date"
                  prepend-inner-icon="mdi-calendar-remove"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  class="employee-form-field"
                ></v-text-field>
              </div>

              <!-- Status -->
              <div class="mb-4">
                <div class="text-body-2 mb-1 font-weight-medium">Status</div>
                <v-select
                  v-model="status"
                  :items="[
                    { title: 'Active', value: 'active' },
                    { title: 'Inactive', value: 'inactive' },
                    { title: 'Terminated', value: 'terminated' }
                  ]"
                  placeholder="Select status"
                  prepend-inner-icon="mdi-account-check"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  class="employee-form-field v-select"
                ></v-select>
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

    <!-- Import CSV Dialog -->
    <v-dialog v-model="showImportDialog" max-width="560px">
      <v-card>
        <v-card-title class="d-flex align-center gap-3">
          <v-avatar color="primary" variant="tonal" size="40">
            <v-icon color="primary">mdi-file-upload</v-icon>
          </v-avatar>
          <div>
            <div class="text-subtitle-1 font-weight-bold">Import Employees</div>
            <div class="text-caption text-medium-emphasis">Upload CSV with required headers</div>
          </div>
        </v-card-title>
        <v-divider />
        <v-card-text class="pt-4">
          <div class="text-body-2 text-medium-emphasis mb-3">
            CSV headers: <strong>first_name, last_name, email, password</strong>. Optional: <strong>employee_no</strong>.
            Password required per row; email must be unique; we’ll generate employee_no if missing.
          </div>
          <v-file-input
            v-model="importFile"
            label="Select CSV file"
            accept=".csv,text/csv"
            prepend-icon="mdi-paperclip"
            variant="outlined"
            density="comfortable"
            show-size
            :disabled="isImporting"
          />
          <v-alert
            v-if="importErrorMessage"
            type="error"
            variant="tonal"
            density="comfortable"
            class="mt-2"
          >
            {{ importErrorMessage }}
          </v-alert>
          <v-alert
            v-if="importResult"
            class="mt-3"
            type="success"
            variant="tonal"
            density="comfortable"
          >
            Imported {{ importResult.created }} successfully. Failed: {{ importResult.failed }}.
          </v-alert>
          <v-alert
            v-if="importErrors.length"
            class="mt-3"
            type="error"
            variant="tonal"
            density="comfortable"
          >
            <div class="font-weight-medium mb-1">Errors:</div>
            <ul class="mb-0 ps-4">
              <li v-for="(err, idx) in importErrors" :key="idx">
                Row {{ err.row }} - {{ err.message }}
              </li>
            </ul>
          </v-alert>
        </v-card-text>
        <v-divider />
        <v-card-actions class="py-3 px-4">
          <v-spacer />
          <v-btn variant="text" @click="closeImportDialog" :disabled="isImporting">Cancel</v-btn>
          <v-btn color="primary" :loading="isImporting" :disabled="isImporting" @click="submitImport">
            Upload
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
import { ref, computed, watch, nextTick } from 'vue'
import {
  useEmployees,
  useCreateEmployee,
  useUpdateEmployee,
  useDeleteEmployee,
  useImportEmployees,
  useCompanies,
  useDepartments,
  usePositions
} from '@/composables'
import { useZodForm } from '@/composables'
import { extractErrorMessage } from '@/composables/common/useErrorMessage'
import { useNotification } from '@/composables'
import {
  createEmployeeSchema,
  type CreateEmployeeFormData
} from '@/validation'
import type { Employee } from '@/types/employee'

// Reactive state
const showCreateDialog = ref(false)
const showDeleteDialog = ref(false)
const editingEmployee = ref<Employee | null>(null)
const selectedEmployee = ref<Employee | null>(null)
const showPassword = ref(false)
const errorMessage = ref<string | null>(null)
const showImportDialog = ref(false)
const importFile = ref<File | null>(null)
const importResult = ref<{ created: number; failed: number; errors?: Array<{ row: number; message: string }> } | null>(null)
const importErrors = ref<Array<{ row: number; message: string }>>([])
const importErrorMessage = ref<string | null>(null)
const formRef = ref<{ validate: () => Promise<{ valid: boolean }> } | null>(null)
// Column filters
const filters = ref({
  employeeNo: '',
  name: '',
  email: '',
  company: '',
  department: '',
  position: '',
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
    employee_no: '',
    company_uuid: null,
    department_uuid: null,
    position_uuid: null,
    employment_type: null,
    hire_date: null,
    termination_date: null,
    status: 'active',
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
const employeeNoField = createField('employee_no')
const companyUuidField = createField('company_uuid')
const departmentUuidField = createField('department_uuid')
const positionUuidField = createField('position_uuid')
const employmentTypeField = createField('employment_type')
const hireDateField = createField('hire_date')
const terminationDateField = createField('termination_date')
const statusField = createField('status')
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

const employeeNo = computed({
  get: () => employeeNoField.value.value as string,
  set: (value: string) => employeeNoField.setValue(value),
})

const companyUuid = computed({
  get: () => companyUuidField.value.value as string | null,
  set: (value: string | null) => companyUuidField.setValue(value),
})

const departmentUuid = computed({
  get: () => departmentUuidField.value.value as string | null,
  set: (value: string | null) => departmentUuidField.setValue(value),
})

const positionUuid = computed({
  get: () => positionUuidField.value.value as string | null,
  set: (value: string | null) => positionUuidField.setValue(value),
})

const employmentType = computed({
  get: () => employmentTypeField.value.value as 'regular' | 'contractual' | 'probationary' | null,
  set: (value: 'regular' | 'contractual' | 'probationary' | null) => employmentTypeField.setValue(value),
})

const hireDate = computed({
  get: () => hireDateField.value.value as string | null,
  set: (value: string | null) => hireDateField.setValue(value),
})

const terminationDate = computed({
  get: () => terminationDateField.value.value as string | null,
  set: (value: string | null) => terminationDateField.setValue(value),
})

const status = computed({
  get: () => statusField.value.value as 'active' | 'inactive' | 'terminated' | null,
  set: (value: 'active' | 'inactive' | 'terminated' | null) => statusField.setValue(value),
})

const password = computed({
  get: () => (passwordField.value.value as string) || '',
  set: (value: string) => passwordField.setValue(value),
})

// Vue Query hooks for cascading dropdowns (after computed properties are defined)
// Pass computed refs directly so they're reactive
const { data: departments } = useDepartments(companyUuid)
const { data: positions } = usePositions(departmentUuid)

// Watch companyUuid to reset department and position when company changes
// Only reset if it's an actual user change (not during edit initialization)
watch(companyUuid, (newCompanyUuid, oldCompanyUuid) => {
  // Only reset if both values are defined (not initial set during edit)
  // This prevents resetting when loading employee data for editing
  if (oldCompanyUuid !== null && oldCompanyUuid !== undefined && newCompanyUuid !== oldCompanyUuid) {
    // Reset department and position when company changes
    setFieldValue('department_uuid', null)
    setFieldValue('position_uuid', null)
  }
})

// Watch departmentUuid to reset position when department changes
// Only reset if it's an actual user change (not during edit initialization)
watch(departmentUuid, (newDepartmentUuid, oldDepartmentUuid) => {
  // Only reset if both values are defined (not initial set during edit)
  // This prevents resetting when loading employee data for editing
  if (oldDepartmentUuid !== null && oldDepartmentUuid !== undefined && newDepartmentUuid !== oldDepartmentUuid) {
    // Reset position when department changes
    setFieldValue('position_uuid', null)
  }
})

// Error messages
const firstNameError = computed(() => firstNameField.errorMessage.value)
const lastNameError = computed(() => lastNameField.errorMessage.value)
const emailError = computed(() => emailField.errorMessage.value)
const employeeNoError = computed(() => employeeNoField.errorMessage.value)
const passwordError = computed(() => passwordField.errorMessage.value)

const hasFirstNameError = computed(() => !!firstNameError.value)
const hasLastNameError = computed(() => !!lastNameError.value)
const hasEmailError = computed(() => !!emailError.value)
const hasEmployeeNoError = computed(() => !!employeeNoError.value)
const hasPasswordError = computed(() => !!passwordError.value)

// Password validation is handled entirely by Zod schema
// No need for additional rules - they would conflict with Zod validation

// Vue Query hooks
const { data: employeesData, isLoading, error, refetch } = useEmployees()
const createMutation = useCreateEmployee()
const updateMutation = useUpdateEmployee()
const deleteMutation = useDeleteEmployee()
const importMutation = useImportEmployees()
const { data: companies } = useCompanies()

// Computed properties
const employees = computed(() => employeesData.value?.data || [])
const isSaving = computed(() => createMutation.isPending.value || updateMutation.isPending.value)
const isDeleting = computed(() => deleteMutation.isPending.value)
const isImporting = computed(() => importMutation.isPending.value)

// Check if any filters are active
const hasActiveFilters = computed(() => {
  return !!(filters.value.employeeNo || filters.value.name || filters.value.email ||
            filters.value.company || filters.value.department || filters.value.position)
})

// Filter employees based on column filters
const filteredEmployees = computed(() => {
  let result = employees.value

  // Filter by Employee No
  if (filters.value.employeeNo) {
    const query = filters.value.employeeNo.toLowerCase()
    result = result.filter((emp) =>
      emp.employee_no?.toLowerCase().includes(query)
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

  // Filter by Company
  if (filters.value.company) {
    const query = filters.value.company.toLowerCase()
    result = result.filter((emp) =>
      emp.company?.name?.toLowerCase().includes(query)
    )
  }

  // Filter by Department
  if (filters.value.department) {
    const query = filters.value.department.toLowerCase()
    result = result.filter((emp) =>
      emp.department?.name?.toLowerCase().includes(query)
    )
  }

  // Filter by Position
  if (filters.value.position) {
    const query = filters.value.position.toLowerCase()
    result = result.filter((emp) =>
      emp.position?.title?.toLowerCase().includes(query)
    )
  }

  return result
})

const headers = [
  { title: 'Employee No', key: 'employee_no', sortable: true },
  { title: 'Name', key: 'user', sortable: true },
  { title: 'Email', key: 'email', sortable: true },
  { title: 'Company', key: 'company', sortable: true },
  { title: 'Department', key: 'department', sortable: true },
  { title: 'Position', key: 'position', sortable: true },
  { title: 'Type', key: 'employment_type', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Hire Date', key: 'hire_date', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const }
]

// Helper function to get user initials
const getInitials = (user: { first_name?: string; last_name?: string } | undefined): string => {
  if (!user) return '??'
  const first = user.first_name?.charAt(0).toUpperCase() || ''
  const last = user.last_name?.charAt(0).toUpperCase() || ''
  return `${first}${last}` || '??'
}

// Helper functions for display
const formatDate = (date: string | null | undefined): string => {
  if (!date) return '-'
  try {
    return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
  } catch {
    return date
  }
}

const getEmploymentTypeColor = (type: string | null | undefined): string => {
  switch (type) {
    case 'regular': return 'success'
    case 'contractual': return 'warning'
    case 'probationary': return 'info'
    default: return 'grey'
  }
}

const getStatusColor = (status: string | null | undefined): string => {
  switch (status) {
    case 'active': return 'success'
    case 'inactive': return 'warning'
    case 'terminated': return 'error'
    default: return 'success'
  }
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

const openImportDialog = () => {
  importFile.value = null
  importResult.value = null
  importErrors.value = []
  importErrorMessage.value = null
  showImportDialog.value = true
}

const closeImportDialog = () => {
  showImportDialog.value = false
  importFile.value = null
  importResult.value = null
  importErrors.value = []
  importErrorMessage.value = null
}

const submitImport = async () => {
  if (!importFile.value) {
    notification.showError('Please select a CSV file to import.')
    return
  }

  try {
    const result = await importMutation.mutateAsync(importFile.value)
    importResult.value = result
    importErrors.value = result.errors || []
    importErrorMessage.value = null

    if (result.failed > 0) {
      notification.showError(`Imported with ${result.failed} error(s).`)
    } else {
      notification.showSuccess('Employees imported successfully!')
    }
  } catch (error: unknown) {
    const message = extractErrorMessage(error)
    importErrorMessage.value = message
    notification.showError(message)
  }
}

const editEmployee = async (employee: Employee) => {
  editingEmployee.value = employee
  setFieldValue('first_name', employee.user?.first_name || '')
  setFieldValue('last_name', employee.user?.last_name || '')
  setFieldValue('email', employee.user?.email || '')
  setFieldValue('employee_no', employee.employee_no || '')

  // Set other fields first
  setFieldValue('employment_type', employee.employment_type || null)
  setFieldValue('hire_date', employee.hire_date || null)
  setFieldValue('termination_date', employee.termination_date || null)
  setFieldValue('status', employee.status || 'active')
  // Set a dummy password that passes validation (required by createEmployeeSchema)
  // This won't be sent to the API - it's only for form validation
  setFieldValue('password', 'dummy_password_for_edit_validation')

  // Open drawer first so queries can run
  showCreateDialog.value = true

  // Wait for drawer to be rendered
  await nextTick()

  // Set company first - this will trigger departments query
  if (employee.company_uuid) {
    setFieldValue('company_uuid', employee.company_uuid)

    // Wait a moment for the query to trigger and fetch departments
    await new Promise(resolve => setTimeout(resolve, 300))

    // Set department if employee has one (departments might be empty array if company has no departments)
    if (employee.department_uuid && departments.value !== undefined) {
      setFieldValue('department_uuid', employee.department_uuid)

      // Wait a moment for positions query to trigger and fetch
      await new Promise(resolve => setTimeout(resolve, 300))

      // Set position if employee has one (positions might be empty array if department has no positions)
      if (employee.position_uuid && positions.value !== undefined) {
        setFieldValue('position_uuid', employee.position_uuid)
      }
    }
  } else {
    // No company, set to null
    setFieldValue('company_uuid', null)
    setFieldValue('department_uuid', null)
    setFieldValue('position_uuid', null)
  }
}

// Handle form submission with validation
const onSubmit = createFormHandler(async (values: CreateEmployeeFormData) => {
  errorMessage.value = null
  clearServerErrors()

  try {
    if (editingEmployee.value) {
      // For updates, exclude password and dummy password
      const updateData = {
        first_name: values.first_name,
        last_name: values.last_name,
        email: values.email,
        employee_no: values.employee_no,
        company_uuid: values.company_uuid,
        department_uuid: values.department_uuid,
        position_uuid: values.position_uuid,
        employment_type: values.employment_type,
        hire_date: values.hire_date,
        termination_date: values.termination_date,
        status: values.status,
      }
      await updateMutation.mutateAsync({
        uuid: editingEmployee.value.uuid,
        data: updateData
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
        employee_no: values.employee_no,
        company_uuid: values.company_uuid,
        department_uuid: values.department_uuid,
        position_uuid: values.position_uuid,
        employment_type: values.employment_type,
        hire_date: values.hire_date,
        termination_date: values.termination_date,
        status: values.status || 'active',
      })

      notification.showSuccess('Employee created successfully!')
    }

    closeDialog()
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
  padding: 6px 12px !important;
  font-size: 0.75rem;
}

.employees-table :deep(.v-data-table__tbody tr) {
  height: 36px !important;
}

.employees-table :deep(.v-data-table__tbody td) {
  padding: 4px 12px !important;
  font-size: 0.75rem;
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

/* Employee Drawer Styles - Moved to global drawer.css */

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
