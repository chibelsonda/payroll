<template>
  <div>
    <h1>Admin Dashboard</h1>
    <p>Welcome, {{ auth.user?.first_name }} {{ auth.user?.last_name }}</p>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-8">
      <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
      <p class="mt-4">Loading dashboard data...</p>
    </div>

    <!-- Error State -->
    <v-alert v-else-if="error" type="error" class="mb-4">
      Failed to load dashboard data: {{ error.message }}
      <template #append>
        <v-btn variant="text" @click="refetch">Retry</v-btn>
      </template>
    </v-alert>

    <!-- Dashboard Content -->
    <div v-else>
      <v-row>
        <v-col cols="12" md="4">
          <v-card>
            <v-card-title>Students</v-card-title>
            <v-card-text>
              <div class="text-h4">{{ studentsCount }}</div>
              <div class="text-caption">Total Students</div>
            </v-card-text>
            <v-card-actions>
              <v-btn to="/admin/students" color="primary">Manage Students</v-btn>
            </v-card-actions>
          </v-card>
        </v-col>

        <v-col cols="12" md="4">
          <v-card>
            <v-card-title>Subjects</v-card-title>
            <v-card-text>
              <div class="text-h4">{{ subjectsCount }}</div>
              <div class="text-caption">Total Subjects</div>
            </v-card-text>
            <v-card-actions>
              <v-btn to="/admin/subjects" color="primary">Manage Subjects</v-btn>
            </v-card-actions>
          </v-card>
        </v-col>

        <v-col cols="12" md="4">
          <v-card>
            <v-card-title>Enrollments</v-card-title>
            <v-card-text>
              <div class="text-h4">{{ enrollmentsCount }}</div>
              <div class="text-caption">Total Enrollments</div>
            </v-card-text>
            <v-card-actions>
              <v-btn to="/admin/enrollments" color="primary">Manage Enrollments</v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>

      <!-- Recent Activity Section -->
      <v-row class="mt-6">
        <v-col cols="12">
          <v-card>
            <v-card-title>Recent Enrollments</v-card-title>
            <v-card-text>
              <v-data-table
                :items="recentEnrollments"
                :headers="enrollmentHeaders"
                :loading="enrollmentsLoading"
                hide-default-footer
                density="compact"
              >
                <template #[`item.student`]="{ item }">
                  {{ item.student?.user?.first_name || 'N/A' }} {{ item.student?.user?.last_name || '' }}
                </template>
                <template #[`item.created_at`]="{ item }">
                  {{ formatDate(item.created_at) }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useQueryClient } from '@tanstack/vue-query'
import { useAuthStore } from '@/stores/auth'
import { useStudents } from '@/composables/useStudents'
import { useSubjects } from '@/composables/useSubjects'
import { useEnrollments } from '@/composables/useEnrollments'

const auth = useAuthStore()

// Only fetch when user is authenticated
const isAuthenticated = computed(() => !!auth.user)
const { data: studentsData, isLoading: studentsLoading, error: studentsError } = useStudents(1, true, isAuthenticated)
const { data: subjectsData, isLoading: subjectsLoading, error: subjectsError } = useSubjects(1, true, isAuthenticated)
const { data: enrollmentsData, isLoading: enrollmentsLoading, error: enrollmentsError } = useEnrollments(1, true, isAuthenticated)

// Computed properties
const isLoading = computed(() => studentsLoading.value || subjectsLoading.value || enrollmentsLoading.value)
const error = computed(() => studentsError.value || subjectsError.value || enrollmentsError.value)

const studentsCount = computed(() => studentsData.value?.data?.length || 0)
const subjectsCount = computed(() => subjectsData.value?.data?.length || 0)
const enrollmentsCount = computed(() => enrollmentsData.value?.data?.length || 0)

const recentEnrollments = computed(() => {
  return enrollmentsData.value?.data?.slice(0, 5) || []
})

const enrollmentHeaders = [
  { title: 'Student', key: 'student', sortable: false },
  { title: 'Subject', key: 'subject.name', sortable: false },
  { title: 'Enrolled At', key: 'created_at', sortable: false },
]

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}

const refetch = () => {
  // Properly refetch all queries instead of reloading the page
  const queryClient = useQueryClient()
  queryClient.invalidateQueries({ queryKey: ['students'] })
  queryClient.invalidateQueries({ queryKey: ['subjects'] })
  queryClient.invalidateQueries({ queryKey: ['enrollments'] })
}
</script>
