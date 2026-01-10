<template>
  <div>
    <h1 class="text-h4 mb-4">Employee Dashboard</h1>
    <p class="text-h6 mb-6">Welcome, {{ fullName }}</p>

    <!-- Dashboard Content -->
    <v-row>
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
                <v-list-item-title>Employee ID</v-list-item-title>
                <v-list-item-subtitle>{{ auth.user?.employee?.employee_no || 'N/A' }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
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
