
import './assets/drawer.css'
import './assets/vuetify.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { VueQueryPlugin, QueryClient } from '@tanstack/vue-query'
// Persistence disabled to ensure fresh data on each page visit
// import { persistQueryClient } from '@tanstack/query-persist-client-core'
// import { createAsyncStoragePersister } from '@tanstack/query-async-storage-persister'

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

// Create Query Client with fresh data on each page visit
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 0, // Data is immediately stale, will refetch on mount
      gcTime: 1000 * 60 * 5, // Keep in cache for 5 minutes (reduced from 24 hours)
      refetchOnMount: true, // Always refetch when component mounts
      refetchOnWindowFocus: false, // Don't refetch on window focus (only on mount)
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

// Persistence disabled to ensure fresh data on each page visit
// If you want to re-enable persistence, uncomment the imports above and this block:
// const persister = createAsyncStoragePersister({
//   storage: window.localStorage,
//   key: 'ces-query-cache',
// })
// persistQueryClient({
//   queryClient,
//   persister,
//   maxAge: 1000 * 60 * 5, // Reduced from 24 hours to 5 minutes
//   dehydrateOptions: {
//     shouldDehydrateQuery: (query) => {
//       if (query.state.status === 'error') {
//         return false
//       }
//       return true
//     },
//   },
// })

// Configure Vue Query
app.use(VueQueryPlugin, {
  queryClient,
})


app.mount('#app')
