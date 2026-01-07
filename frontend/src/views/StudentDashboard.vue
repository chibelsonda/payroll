<template>
  <div>
    <h1 class="text-h4 mb-4">Student Dashboard</h1>
    <p class="text-h6 mb-6">Welcome, {{ fullName }}</p>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-8">
      <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
      <p class="mt-4">Loading dashboard data...</p>
    </div>

    <!-- Error State -->
    <v-alert v-else-if="error" type="error" class="mb-4">
      Failed to load enrollments: {{ error.message }}
      <template #append>
        <v-btn variant="text" @click="refetch">Retry</v-btn>
      </template>
    </v-alert>

    <!-- Dashboard Content -->
    <v-row v-else>
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon icon="mdi-account-circle" class="mr-2"></v-icon>
            My Profile
          </v-card-title>
          <v-card-text>
            <v-list density="compact">
              <v-list-item>
                <v-list-item-title>Name</v-list-item-title>
                <v-list-item-subtitle>{{ fullName }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>Email</v-list-item-title>
                <v-list-item-subtitle>{{ auth.user?.email }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>Student ID</v-list-item-title>
                <v-list-item-subtitle>{{ auth.user?.student?.student_id || 'N/A' }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon icon="mdi-book-open-page-variant" class="mr-2"></v-icon>
            My Enrollments
          </v-card-title>
          <v-card-text>
            <div class="text-h3 mb-2">{{ enrollmentsCount }}</div>
            <div class="text-caption">Enrolled Subjects</div>
          </v-card-text>
          <v-card-actions>
            <v-btn 
              to="/student/enrollments" 
              color="primary"
              prepend-icon="mdi-arrow-right"
            >
              View Details
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useEnrollments } from '@/composables/useEnrollments'

const auth = useAuthStore()

// Use composable instead of direct axios call
const { data: enrollmentsData, isLoading, error, refetch } = useEnrollments()

// Computed properties
const enrollmentsCount = computed(() => enrollmentsData.value?.data?.length || 0)

const fullName = computed(() => {
  const user = auth.user
  if (!user) return 'Guest'
  return user.first_name && user.last_name 
    ? `${user.first_name} ${user.last_name}` 
    : user.name || user.email
})
</script>