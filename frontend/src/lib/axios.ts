import axios from 'axios'

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

// Interceptor to suppress console logging of 401 errors (expected when not authenticated)
apiAxios.interceptors.response.use(
  (response) => response,
  (error) => {
    // Suppress console logging for 401 errors on protected endpoints
    // These errors are expected when user is not authenticated
    if (error.response?.status === 401) {
      // Mark as silent - let handlers deal with it without console spam
      const silentError = Object.assign(error, {
        isSilent: true,
      })
      return Promise.reject(silentError)
    }
    return Promise.reject(error)
  }
)

export default apiAxios
export { webAxios }
