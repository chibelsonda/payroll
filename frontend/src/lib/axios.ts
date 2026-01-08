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

// Interceptor to suppress 401 errors for /user endpoint (expected when not authenticated)
// apiAxios.interceptors.response.use(
//   (response) => response,
//   (error) => {
//     // Mark 401 on /user endpoint as silent (expected when not authenticated)
//     if (error.config?.url?.includes('/user') && error.response?.status === 401) {
//       // Create error with silent flag - let the catch handler deal with it
//       const silentError = Object.assign(error, {
//         isSilent: true,
//       })
//       return Promise.reject(silentError)
//     }
//     return Promise.reject(error)
//   }
// )

export default apiAxios
export { webAxios }
