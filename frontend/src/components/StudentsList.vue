<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title>
            <h2>Students Management</h2>
            <v-spacer></v-spacer>
            <v-btn color="primary" @click="showCreateDialog = true">
              <v-icon left>mdi-plus</v-icon>
              Add Student
            </v-btn>
          </v-card-title>

          <v-card-text>
            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
              <p class="mt-4">Loading students...</p>
            </div>

            <!-- Error State -->
            <v-alert v-else-if="error" type="error" class="mb-4">
              Failed to load students: {{ error.message }}
              <template #append>
                <v-btn variant="text" @click="refetch">Retry</v-btn>
              </template>
            </v-alert>

            <!-- Students Table -->
            <v-data-table
              v-else
              :items="students"
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
                  @click="editStudent(item)"
                ></v-btn>
                <v-btn
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  color="error"
                  @click="deleteStudent(item)"
                ></v-btn>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Create/Edit Student Dialog -->
    <v-dialog v-model="showCreateDialog" max-width="500px">
      <v-card>
        <v-card-title>
          {{ editingStudent ? 'Edit Student' : 'Create Student' }}
        </v-card-title>
        <v-card-text>
          <v-form ref="form" v-model="formValid">
            <v-text-field
              v-model="studentForm.first_name"
              label="First Name"
              :rules="[v => !!v || 'First name is required']"
              required
              density="compact"
              variant="outlined"
            ></v-text-field>
            <v-text-field
              v-model="studentForm.last_name"
              label="Last Name"
              :rules="[v => !!v || 'Last name is required']"
              required
              density="compact"
              variant="outlined"
            ></v-text-field>
            <v-text-field
              v-model="studentForm.email"
              label="Email"
              type="email"
              :rules="[v => !!v || 'Email is required']"
              required
              density="compact"
              variant="outlined"
            ></v-text-field>
            <v-text-field
              v-model="studentForm.student_id"
              label="Student ID"
              :rules="[v => !!v || 'Student ID is required']"
              required
              density="compact"
              variant="outlined"
            ></v-text-field>
            <v-text-field
              v-if="!editingStudent"
              v-model="studentForm.password"
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
            @click="saveStudent"
          >
            {{ editingStudent ? 'Update' : 'Create' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="showDeleteDialog" max-width="400px">
      <v-card>
        <v-card-title>Confirm Delete</v-card-title>
        <v-card-text>
          Are you sure you want to delete student "{{ selectedStudent?.user?.first_name }} {{ selectedStudent?.user?.last_name }}"?
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
  useStudents,
  useCreateStudent,
  useUpdateStudent,
  useDeleteStudent
} from '@/composables'
import { useRegister } from '@/composables/useAuth'
import type { Student } from '@/types/auth'

// Reactive state
const showCreateDialog = ref(false)
const showDeleteDialog = ref(false)
const editingStudent = ref<Student | null>(null)
const selectedStudent = ref<Student | null>(null)
const formValid = ref(false)

// Form data
const studentForm = reactive({
  first_name: '',
  last_name: '',
  email: '',
  student_id: '',
  password: ''
})

// Vue Query hooks
const { data: studentsData, isLoading, error, refetch } = useStudents()
const createMutation = useCreateStudent()
const updateMutation = useUpdateStudent()
const deleteMutation = useDeleteStudent()
const registerMutation = useRegister()

// Computed properties
const students = computed(() => studentsData.value?.data || [])

const headers = [
  { title: 'Student ID', key: 'student_id' },
  { title: 'Name', key: 'user' },
  { title: 'Email', key: 'user.email' },
  { title: 'Actions', key: 'actions', sortable: false }
]

// Methods
const resetForm = () => {
  Object.assign(studentForm, {
    first_name: '',
    last_name: '',
    email: '',
    student_id: '',
    password: ''
  })
  editingStudent.value = null
}

const closeDialog = () => {
  showCreateDialog.value = false
  resetForm()
}

const editStudent = (student: Student) => {
  editingStudent.value = student
  studentForm.first_name = student.user.first_name
  studentForm.last_name = student.user.last_name
  studentForm.email = student.user.email
  studentForm.student_id = student.student_id
  showCreateDialog.value = true
}

const saveStudent = async () => {
  if (!formValid.value) return

  try {
    const formData = {
      first_name: studentForm.first_name,
      last_name: studentForm.last_name,
      email: studentForm.email,
      student_id: studentForm.student_id,
      ...(editingStudent.value ? {} : { password: studentForm.password })
    }

    if (editingStudent.value) {
      // For updates, we need to update the user data separately
      // This is a simplified example - in a real app you'd have a dedicated endpoint
      await updateMutation.mutateAsync({
        uuid: editingStudent.value.uuid,
        data: { student_id: studentForm.student_id }
      })
    } else {
      await createMutation.mutateAsync(formData)
    }

    closeDialog()
  } catch (error) {
    console.error('Failed to save student:', error)
  }
}

const deleteStudent = (student: Student) => {
  selectedStudent.value = student
  showDeleteDialog.value = true
}

const confirmDelete = async () => {
  if (!selectedStudent.value) return

  try {
    await deleteMutation.mutateAsync(selectedStudent.value.uuid)
    showDeleteDialog.value = false
    selectedStudent.value = null
  } catch (error) {
    console.error('Failed to delete student:', error)
  }
}
</script>
