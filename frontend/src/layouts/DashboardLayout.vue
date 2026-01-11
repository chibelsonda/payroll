<template>
  <v-app>
    <v-navigation-drawer v-model="drawer" app class="sidebar">
      <v-list class="pa-2">
        <v-list-item class="px-3 mb-2">
          <v-list-item-title class="text-h6 font-weight-bold text-primary">
            {{ title }}
          </v-list-item-title>
        </v-list-item>
        <v-divider class="mb-2"></v-divider>
        <template v-for="item in menuItems" :key="item.to || item.title">
          <!-- Regular menu item -->
          <v-list-item
            v-if="!item.children && item.to"
            :to="item.to"
            :prepend-icon="item.icon"
            :active="isActiveRoute(item.to)"
            class="sidebar-item mb-1"
          >
            <v-list-item-title class="text-body-2">{{ item.title }}</v-list-item-title>
          </v-list-item>

          <!-- Menu item with dropdown -->
          <v-list-group
            v-else-if="item.children"
            :value="isGroupActive(item)"
            class="sidebar-item mb-1"
          >
            <template #activator="{ props }">
              <v-list-item
                v-bind="props"
                :prepend-icon="item.icon"
                class="sidebar-item"
              >
                <v-list-item-title class="text-body-2">{{ item.title }}</v-list-item-title>
              </v-list-item>
            </template>
            <v-list-item
              v-for="child in item.children"
              :key="child.to || child.title"
              :to="child.to"
              :active="child.to ? isActiveRoute(child.to) : false"
              class="sidebar-subitem ms-4"
            >
              <v-list-item-title class="text-body-2">{{ child.title }}</v-list-item-title>
            </v-list-item>
          </v-list-group>
        </template>
      </v-list>
    </v-navigation-drawer>

    <v-app-bar app elevation="2" class="app-bar">
      <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>

      <!-- Search Box -->
      <div class="app-bar-search-wrapper mx-4">
        <v-icon class="search-icon">mdi-magnify</v-icon>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search..."
          class="app-bar-search-input"
        />
        <v-icon
          v-if="searchQuery"
          class="clear-icon"
          @click="searchQuery = ''"
        >
          mdi-close
        </v-icon>
      </div>

      <v-spacer></v-spacer>

      <!-- User Info Section with Context Menu -->
      <v-menu>
        <template #activator="{ props }">
          <div
            v-bind="props"
            class="d-flex align-center me-4 cursor-pointer"
            style="cursor: pointer;"
          >
            <v-avatar size="32" color="white" class="me-2">
              <span class="text-primary text-caption font-weight-bold">
                {{ getUserInitials() }}
              </span>
            </v-avatar>
            <div class="d-none d-sm-flex flex-column">
              <span class="text-caption font-weight-medium" style="color: rgba(0, 0, 0, 0.87);">
                {{ getUserFullName() }}
              </span>
              <span class="text-caption" style="color: rgba(0, 0, 0, 0.6);">
                {{ auth.user?.role === 'admin' ? 'Administrator' : 'Employee' }}
              </span>
            </div>
            <v-icon size="16" class="ms-2 d-none d-sm-inline">mdi-chevron-down</v-icon>
          </div>
        </template>
        <v-list density="compact">
          <v-list-item>
            <v-list-item-title class="text-body-2 font-weight-medium">
              {{ getUserFullName() }}
            </v-list-item-title>
            <v-list-item-subtitle class="text-caption">
              {{ auth.user?.email }}
            </v-list-item-subtitle>
          </v-list-item>
          <v-divider class="my-2"></v-divider>
          <v-list-item
            prepend-icon="mdi-logout"
            title="Logout"
            @click="handleLogout"
            :disabled="auth.isLogoutLoading"
          >
            <template #append v-if="auth.isLogoutLoading">
              <v-progress-circular
                indeterminate
                size="16"
                width="2"
              ></v-progress-circular>
            </template>
          </v-list-item>
        </v-list>
      </v-menu>
    </v-app-bar>

    <v-main>
      <v-container fluid class="pa-0">
        <div class="pa-4">
          <router-view />
        </div>
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
  to?: string
  icon?: string
  children?: MenuItem[]
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
const searchQuery = ref('')

// Check if a route is currently active
const isActiveRoute = (path: string): boolean => {
  const currentPath = route.path

  // Only exact matches should be highlighted
  // This prevents parent routes (e.g., /admin) from being highlighted when on child routes (e.g., /admin/employees)
  return currentPath === path
}

// Check if any child route is active (for expanding the group)
const isGroupActive = (item: MenuItem): boolean => {
  if (!item.children) return false
  return item.children.some(child => isActiveRoute(child.to || ''))
}

// Get user initials for avatar
const getUserInitials = (): string => {
  const user = auth.user
  if (!user) return '??'
  const first = user.first_name?.charAt(0).toUpperCase() || ''
  const last = user.last_name?.charAt(0).toUpperCase() || ''
  return `${first}${last}` || '??'
}

// Get user full name
const getUserFullName = (): string => {
  const user = auth.user
  if (!user) return 'Guest'
  if (user.first_name && user.last_name) {
    return `${user.first_name} ${user.last_name}`
  }
  return user.email || 'User'
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

<style scoped>
.sidebar :deep(.v-list-item) {
  min-height: 40px;
}

.sidebar-item :deep(.v-list-item-title) {
  font-size: 0.875rem;
  font-weight: 500;
}

.sidebar-item :deep(.v-list-item__prepend) {
  margin-inline-end: -20px !important;
}

.sidebar-item :deep(.v-icon) {
  font-size: 20px;
}

.sidebar-item.v-list-item--active {
  background-color: rgba(25, 118, 210, 0.12);
  color: rgb(25, 118, 210);
  border-radius: 4px !important;
}

.sidebar-item.v-list-item--active :deep(.v-list-item-title) {
  color: rgb(25, 118, 210);
  font-weight: 600;
}

.sidebar-item:hover {
  background-color: rgba(0, 0, 0, 0.04);
  border-radius: 4px !important;
}

.sidebar-subitem {
  min-height: 36px !important;
  border-radius: 4px !important;
  margin-left: -10px !important;
  padding-left: 0 !important;
}

.sidebar-subitem.v-list-item--active {
  background-color: rgba(25, 118, 210, 0.12);
  color: rgb(25, 118, 210);
}

.sidebar-subitem.v-list-item--active :deep(.v-list-item-title) {
  color: rgb(25, 118, 210);
  font-weight: 600;
}

.sidebar-subitem:hover {
  background-color: rgba(0, 0, 0, 0.04);
}

.sidebar :deep(.v-list-group__items) {
  padding-left: 0 !important;
}

/* App Bar Enhancements */
.app-bar {
  background: rgb(227, 242, 253) !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
  height: 60px !important;
  min-height: 60px !important;
}

.app-bar :deep(.v-icon) {
  color: rgba(0, 0, 0, 0.87) !important;
}

.app-bar :deep(.v-toolbar__content) {
  height: 60px !important;
  min-height: 60px !important;
}

.app-bar :deep(.v-toolbar-title) {
  font-weight: 600;
  letter-spacing: 0.5px;
  color: rgba(0, 0, 0, 0.87) !important;
}

/* Search Box Styling */
.app-bar-search-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  max-width: 350px;
  min-width: 250px;
  background-color: white;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 5px;
  padding: 6px 16px;
  transition: border-color 0.2s ease;
  box-sizing: border-box;
}

.app-bar-search-wrapper:hover {
  border-color: rgba(0, 0, 0, 0.24);
}

.app-bar-search-wrapper:focus-within {
  border-color: rgb(25, 118, 210);
  border-width: 2px;
  padding: 5px 15px;
}

.search-icon {
  color: rgba(0, 0, 0, 0.6) !important;
  margin-right: 8px;
  font-size: 20px;
}

.app-bar-search-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: rgba(0, 0, 0, 0.87);
  font-size: 0.9rem;
  padding: 0;
}

.app-bar-search-input::placeholder {
  color: rgba(0, 0, 0, 0.38);
}

.clear-icon {
  color: rgba(0, 0, 0, 0.54) !important;
  margin-left: 8px;
  cursor: pointer;
  font-size: 18px;
}

.clear-icon:hover {
  color: rgba(0, 0, 0, 0.87) !important;
}

/* Hide search on very small screens */
@media (max-width: 600px) {
  .app-bar-search-wrapper {
    display: none;
  }
}

</style>
