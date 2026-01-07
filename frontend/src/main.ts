//import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { VueQueryPlugin, QueryClient } from '@tanstack/vue-query'
import { persistQueryClient } from '@tanstack/query-persist-client-core'
import { createSyncStoragePersister } from '@tanstack/query-sync-storage-persister'

import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'
import axios from 'axios'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(vuetify)

// Create Query Client with persistence-friendly settings
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5 minutes
      gcTime: 1000 * 60 * 60 * 24, // 24 hours (cache time for persistence)
      retry: (failureCount, error: any) => {
        // Don't retry on 4xx errors
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
  key: 'ces-query-cache', // Unique key for this app
})

// Enable persistence - data survives page refresh
persistQueryClient({
  queryClient,
  persister,
  maxAge: 1000 * 60 * 60 * 24, // 24 hours max cache age
  dehydrateOptions: {
    shouldDehydrateQuery: (query) => {
      // Don't persist queries that are in error state
      if (query.state.status === 'error') {
        return false
      }
      // Don't persist user query if no token (logged out)
      if (query.queryKey[0] === 'user' && !localStorage.getItem('token')) {
        return false
      }
      return true
    },
  },
})

// Configure Vue Query with existing query client
app.use(VueQueryPlugin, {
  queryClient,
})

// Configure axios
axios.defaults.baseURL = 'http://localhost:8000'
axios.defaults.headers.common['Accept'] = 'application/json'

// Add axios interceptor for auth token
axios.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

app.mount('#app')
