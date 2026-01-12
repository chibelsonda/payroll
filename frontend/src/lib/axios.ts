import axios from 'axios'
import { useLoadingStore } from '@/stores/loading'

const apiBaseURL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1'
const webBaseURL = import.meta.env.VITE_WEB_BASE_URL || 'http://localhost:8000'

const commonConfig = {
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  withCredentials: true,
  withXSRFToken: true,
}

// Helper function to setup loading interceptors
const setupLoadingInterceptors = (instance: ReturnType<typeof axios.create>) => {
  // Request interceptor - increment loading counter and add company header
  instance.interceptors.request.use(
    (config) => {
      const loadingStore = useLoadingStore()
      loadingStore.increment()
      
      // Add X-Company-ID header if active company is set
      // Get from localStorage directly to avoid circular dependencies
      const stored = localStorage.getItem('active_company_uuid')
      if (stored) {
        config.headers['X-Company-ID'] = stored
      }
      
      return config
    },
    (error) => {
      const loadingStore = useLoadingStore()
      loadingStore.decrement()
      return Promise.reject(error)
    }
  )

  // Response interceptor - decrement loading counter
  instance.interceptors.response.use(
    (response) => {
      const loadingStore = useLoadingStore()
      loadingStore.decrement()
      return response
    },
    (error) => {
      const loadingStore = useLoadingStore()
      loadingStore.decrement()
      return Promise.reject(error)
    }
  )
}

// API axios instance (for /api/v1 routes)
const apiAxios = axios.create({
  ...commonConfig,
  baseURL: apiBaseURL,
})

// Web axios instance (for /login, /logout, /register, /sanctum/csrf-cookie)
const webAxios = axios.create({
  ...commonConfig,
  baseURL: webBaseURL,
})

// Setup loading interceptors for both instances
setupLoadingInterceptors(apiAxios)
setupLoadingInterceptors(webAxios)

export default apiAxios
export { webAxios }
