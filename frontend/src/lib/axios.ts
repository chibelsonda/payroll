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

export default apiAxios
export { webAxios }
