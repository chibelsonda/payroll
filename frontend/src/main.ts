//import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { VueQueryPlugin, QueryClient } from '@tanstack/vue-query'
import { persistQueryClient } from '@tanstack/query-persist-client-core'
import { createSyncStoragePersister } from '@tanstack/query-sync-storage-persister'

import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'
import '@/lib/axios'
import { useAuthStore } from '@/stores/auth'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(vuetify)

// Create Query Client with persistence-friendly settings
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5,
      gcTime: 1000 * 60 * 60 * 24,
      retry: (failureCount, error: any) => {
        if (error?.response?.status >= 400 && error?.response?.status < 500) {
          return false
        }
        return failureCount < 3
      },
    },
    mutations: {
      retry: false,
    },
  },
})

// Create localStorage persister
const persister = createSyncStoragePersister({
  storage: window.localStorage,
  key: 'ces-query-cache',
})

// Enable persistence
persistQueryClient({
  queryClient,
  persister,
  maxAge: 1000 * 60 * 60 * 24,
  dehydrateOptions: {
    shouldDehydrateQuery: (query) => {
      if (query.state.status === 'error') {
        return false
      }
      return true
    },
  },
})

// Configure Vue Query
app.use(VueQueryPlugin, {
  queryClient,
})

// Fetch current user on app load
const authStore = useAuthStore()
authStore.fetchUser().catch(() => {
  // Silently fail if user is not authenticated
})

app.mount('#app')
