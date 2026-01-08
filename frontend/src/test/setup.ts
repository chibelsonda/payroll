import { vi } from 'vitest'
import { config } from '@vue/test-utils'

// Mock Vuetify components globally
config.global.stubs = {
  'v-container': true,
  'v-row': true,
  'v-col': true,
  'v-card': true,
  'v-card-text': true,
  'v-form': true,
  'v-text-field': true,
  'v-btn': true,
  'v-alert': true,
  'v-icon': true,
  'router-link': true,
  'router-view': true,
}

// Mock environment variables
vi.stubEnv('VITE_API_BASE_URL', 'http://localhost:8000/api/v1')
vi.stubEnv('VITE_WEB_BASE_URL', 'http://localhost:8000')
