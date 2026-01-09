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
        <v-col cols="12" md="6">
          <v-card>
            <v-card-title>Employees</v-card-title>
            <v-card-text>
              <div class="text-h4">{{ employeesCount }}</div>
              <div class="text-caption">Total Employees</div>
            </v-card-text>
            <v-card-actions>
              <v-btn to="/admin/employees" color="primary">Manage Employees</v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useEmployees } from '@/composables/useEmployees'

const auth = useAuthStore()

// Only fetch when user is authenticated
const isAuthenticated = computed(() => !!auth.user)
const { data: employeesData, isLoading: employeesLoading, error: employeesError, refetch } = useEmployees(1, true, isAuthenticated)

// Computed properties
const isLoading = computed(() => employeesLoading.value)
const error = computed(() => employeesError.value)

const employeesCount = computed(() => employeesData.value?.data?.length || 0)
</script>
