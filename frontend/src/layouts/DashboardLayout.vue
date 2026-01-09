<template>
  <v-app>
    <v-navigation-drawer v-model="drawer" app>
      <v-list>
        <v-list-item>
          <v-list-item-title class="text-h6">{{ title }}</v-list-item-title>
        </v-list-item>
        <v-divider class="my-2"></v-divider>
        <v-list-item
          v-for="item in menuItems"
          :key="item.to"
          :to="item.to"
          :prepend-icon="item.icon"
          :active="isActiveRoute(item.to)"
        >
          <v-list-item-title>{{ item.title }}</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>

    <v-app-bar app color="primary" dark>
      <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>
      <v-toolbar-title>{{ appBarTitle }}</v-toolbar-title>
      <v-spacer></v-spacer>
      <v-btn
        prepend-icon="mdi-logout"
        variant="text"
        @click="handleLogout"
        :loading="auth.isLogoutLoading"
      >
        Logout
      </v-btn>
    </v-app-bar>

    <v-main>
      <v-container fluid>
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter, useRoute } from 'vue-router'
import { useNotification } from '@/composables/useNotification'

interface MenuItem {
  title: string
  to: string
  icon?: string
}

interface Props {
  title: string
  menuItems: MenuItem[]
  appBarTitle?: string
}

withDefaults(defineProps<Props>(), {
  appBarTitle: 'Payroll System'
})

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const notification = useNotification()
const drawer = ref(true)

// Check if a route is currently active
const isActiveRoute = (path: string): boolean => {
  const currentPath = route.path

  // Only exact matches should be highlighted
  // This prevents parent routes (e.g., /admin) from being highlighted when on child routes (e.g., /admin/employees)
  return currentPath === path
}

const handleLogout = async () => {
  try {
    await auth.logout()
    // Use replace instead of push to prevent back navigation to protected routes
    router.replace('/login')
    notification.showSuccess('Logged out successfully')
  } catch (error) {
    notification.showError('Failed to logout. Please try again.')
    console.error('Logout failed:', error)
    // Force redirect even on error to ensure user leaves protected area
    router.replace('/login')
  }
}
</script>
