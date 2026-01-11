
import './assets/drawer.css'
import './assets/vuetify.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { VueQueryPlugin, QueryClient } from '@tanstack/vue-query'
import { persistQueryClient } from '@tanstack/query-persist-client-core'
import { createAsyncStoragePersister } from '@tanstack/query-async-storage-persister'

import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'

const app = createApp(App)

// Initialize Pinia first (needed for axios interceptors)
app.use(createPinia())

// Import axios after Pinia is initialized (so stores are available in interceptors)
import '@/lib/axios'

app.use(router)
app.use(vuetify)

// Create Query Client with persistence-friendly settings
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5,
      gcTime: 1000 * 60 * 60 * 24,
      retry: (failureCount, error: unknown) => {
        const axiosError = error as { response?: { status?: number }; isSilent?: boolean }
        // Don't retry on 4xx errors or silent errors
        const status = axiosError?.response?.status
        if (status !== undefined && status >= 400 && status < 500) {
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
const persister = createAsyncStoragePersister({
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


app.mount('#app')
