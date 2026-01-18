<template>
  <div>
    <!-- Welcome Header -->
    <div class="mb-6">
      <h1 class="text-h4 font-weight-bold mb-2">Welcome back, {{ auth.user?.first_name }}!</h1>
      <p class="text-body-1 text-medium-emphasis">Here's what's happening with your payroll system today.</p>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-12">
      <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
      <p class="mt-4 text-body-1 text-medium-emphasis">Loading dashboard data...</p>
    </div>

    <!-- Error State -->
    <v-alert v-else-if="error" type="error" variant="tonal" class="mb-4" rounded="lg">
      <div class="d-flex align-center">
        <v-icon class="me-3">mdi-alert-circle</v-icon>
        <div class="flex-grow-1">
          <div class="font-weight-medium">Failed to load dashboard data</div>
          <div class="text-caption">{{ error.message }}</div>
        </div>
        <v-btn variant="text" color="error" @click="refetch">Retry</v-btn>
      </div>
    </v-alert>

    <!-- Dashboard Content -->
    <div v-else>
      <v-row>
        <!-- Employees Card -->
        <v-col cols="12" md="6" lg="4">
          <v-card
            class="stat-card"
            elevation="2"
            rounded="lg"
            :class="{ 'stat-card-employees': true }"
          >
            <v-card-text class="pa-6">
              <div class="d-flex align-center justify-space-between mb-4">
                <v-avatar
                  color="primary"
                  size="56"
                  class="stat-icon"
                >
                  <v-icon size="32" color="white">mdi-account-group</v-icon>
                </v-avatar>
                <v-chip
                  color="primary"
                  variant="flat"
                  size="small"
                >
                  Total
                </v-chip>
              </div>
              <div class="text-h3 font-weight-bold mb-1">{{ employeesCount }}</div>
              <div class="text-body-2 text-medium-emphasis mb-4">Total Employees</div>
              <v-btn
                to="/admin/employees"
                color="primary"
                variant="flat"
                block
                prepend-icon="mdi-arrow-right"
              >
                Manage Employees
              </v-btn>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Quick Actions Card -->
        <v-col cols="12" md="6" lg="4">
          <v-card
            class="stat-card"
            elevation="2"
            rounded="lg"
          >
            <v-card-title class="text-h6 font-weight-medium pb-2">
              Quick Actions
            </v-card-title>
            <v-card-text class="pa-6">
              <v-list density="compact" class="pa-0">
                <v-list-item
                  to="/admin/employees"
                  prepend-icon="mdi-account-plus"
                  title="Add New Employee"
                  class="px-0"
                >
                </v-list-item>
                <v-divider class="my-2"></v-divider>
                <v-list-item
                  to="/admin/employees"
                  prepend-icon="mdi-file-export"
                  title="Export Data"
                  class="px-0"
                >
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- System Status Card -->
        <v-col cols="12" md="6" lg="4">
          <v-card
            class="stat-card"
            elevation="2"
            rounded="lg"
          >
            <v-card-title class="text-h6 font-weight-medium pb-2">
              System Status
            </v-card-title>
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon color="success" class="me-2">mdi-check-circle</v-icon>
                <span class="text-body-1">All systems operational</span>
              </div>
              <div class="d-flex align-center mb-2">
                <v-icon color="success" size="small" class="me-2">mdi-circle</v-icon>
                <span class="text-caption text-medium-emphasis">Database: Connected</span>
              </div>
              <div class="d-flex align-center">
                <v-icon color="success" size="small" class="me-2">mdi-circle</v-icon>
                <span class="text-caption text-medium-emphasis">API: Online</span>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useEmployees } from '@/composables'

const auth = useAuthStore()

// Only fetch when user is authenticated
const isAuthenticated = computed(() => !!auth.user)
const { data: employeesData, isLoading: employeesLoading, error: employeesError, refetch } = useEmployees(1, true, isAuthenticated)

// Computed properties
const isLoading = computed(() => employeesLoading.value)
const error = computed(() => employeesError.value)

const employeesCount = computed(() => employeesData.value?.data?.length || 0)
</script>

<style scoped>
.stat-card {
  transition: transform 0.2s, box-shadow 0.2s;
  height: 100%;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12) !important;
}

.stat-icon {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.stat-card-employees {
  background: linear-gradient(135deg, rgba(25, 118, 210, 0.05) 0%, rgba(25, 118, 210, 0.02) 100%);
}
</style>
