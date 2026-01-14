<template>
  <v-container fluid class="pa-6">
    <!-- Header -->
    <v-row>
      <v-col cols="12">
        <div class="text-h4 font-weight-bold mb-2">My Profile</div>
        <div class="text-body-1 text-medium-emphasis mb-6">
          View and manage your profile information.
        </div>
      </v-col>
    </v-row>

    <!-- Profile Content -->
    <v-row>
      <v-col cols="12" md="8">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center">
              <v-icon class="me-2" color="primary">mdi-account</v-icon>
              <h2 class="text-h6 font-weight-bold mb-0">Profile Information</h2>
            </div>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="pa-6">
            <v-list density="comfortable" class="pa-0">
              <v-list-item class="px-0">
                <v-list-item-title class="text-body-2 text-medium-emphasis mb-1">Full Name</v-list-item-title>
                <v-list-item-subtitle class="text-body-1 font-weight-medium">{{ fullName }}</v-list-item-subtitle>
              </v-list-item>
              <v-divider class="my-4"></v-divider>
              <v-list-item class="px-0">
                <v-list-item-title class="text-body-2 text-medium-emphasis mb-1">Email Address</v-list-item-title>
                <v-list-item-subtitle class="text-body-1 font-weight-medium">{{ auth.user?.email }}</v-list-item-subtitle>
              </v-list-item>
              <v-divider class="my-4"></v-divider>
              <v-list-item class="px-0">
                <v-list-item-title class="text-body-2 text-medium-emphasis mb-1">Employee ID</v-list-item-title>
                <v-list-item-subtitle class="text-body-1 font-weight-medium">
                  {{ auth.user?.employee?.employee_no || 'N/A' }}
                </v-list-item-subtitle>
              </v-list-item>
              <v-divider v-if="auth.user?.employee?.department" class="my-4"></v-divider>
              <v-list-item v-if="auth.user?.employee?.department" class="px-0">
                <v-list-item-title class="text-body-2 text-medium-emphasis mb-1">Department</v-list-item-title>
                <v-list-item-subtitle class="text-body-1 font-weight-medium">
                  {{ auth.user?.employee?.department?.name || 'N/A' }}
                </v-list-item-subtitle>
              </v-list-item>
              <v-divider v-if="auth.user?.employee?.position" class="my-4"></v-divider>
              <v-list-item v-if="auth.user?.employee?.position" class="px-0">
                <v-list-item-title class="text-body-2 text-medium-emphasis mb-1">Position</v-list-item-title>
                <v-list-item-subtitle class="text-body-1 font-weight-medium">
                  {{ auth.user?.employee?.position?.name || 'N/A' }}
                </v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Account Actions -->
      <v-col cols="12" md="4">
        <v-card elevation="2" rounded="lg">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center">
              <v-icon class="me-2" color="primary">mdi-cog</v-icon>
              <h2 class="text-h6 font-weight-bold mb-0">Account</h2>
            </div>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="pa-4">
            <v-alert
              type="info"
              variant="tonal"
              density="compact"
              rounded="lg"
              class="mb-4"
            >
              <div class="text-caption">
                To update your profile information, please contact your administrator.
              </div>
            </v-alert>
          </v-card-text>
        </v-card>

        <!-- Quick Links -->
        <v-card elevation="2" rounded="lg" class="mt-4">
          <v-card-title class="px-4 py-3">
            <div class="d-flex align-center">
              <v-icon class="me-2" color="primary">mdi-link</v-icon>
              <h2 class="text-h6 font-weight-bold mb-0">Quick Links</h2>
            </div>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="pa-4">
            <v-list density="compact" class="pa-0">
              <v-list-item
                to="/employee"
                prepend-icon="mdi-view-dashboard"
                title="Dashboard"
                class="px-0"
              >
              </v-list-item>
              <v-divider class="my-2"></v-divider>
              <v-list-item
                to="/employee/attendance"
                prepend-icon="mdi-calendar-clock"
                title="Attendance"
                class="px-0"
              >
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const fullName = computed(() => {
  const user = auth.user
  if (!user) return 'Guest'
  if (user.first_name && user.last_name) {
    return `${user.first_name} ${user.last_name}`
  }
  return user.email
})
</script>

<style scoped>
</style>
