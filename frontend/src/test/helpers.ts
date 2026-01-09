import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import { config, type ComponentMountingOptions } from '@vue/test-utils'
import { vi } from 'vitest'
import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query'
import type { Router } from 'vue-router'

/**
 * Creates a test router instance
 */
export function createTestRouter(): Router {
  const router = createRouter({
    history: createWebHistory(),
    routes: [
      {
        path: '/',
        name: 'home',
        component: { template: '<div>Home</div>' },
      },
      {
        path: '/login',
        name: 'login',
        component: { template: '<div>Login</div>' },
      },
      {
        path: '/register',
        name: 'register',
        component: { template: '<div>Register</div>' },
      },
      {
        path: '/admin',
        name: 'admin-dashboard',
        component: { template: '<div>Admin Dashboard</div>' },
      },
      {
        path: '/employee',
        name: 'employee-dashboard',
        component: { template: '<div>Employee Dashboard</div>' },
      },
    ],
  })
  // Set initial route to avoid warnings
  router.push('/')
  return router
}

/**
 * Sets up Pinia for testing
 */
export function setupPinia() {
  setActivePinia(createPinia())
}

/**
 * Creates default mounting options with router, pinia, and vue-query
 */
export function createMountOptions(
  options?: Partial<ComponentMountingOptions<unknown>>
): ComponentMountingOptions<unknown> {
  const router = createTestRouter()
  setupPinia()

  // Create QueryClient for tests
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
      },
      mutations: {
        retry: false,
      },
    },
  })

  // Merge stubs - render functional stubs that actually work
  const defaultStubs = {
    'v-container': { template: '<div class="v-container"><slot /></div>' },
    'v-row': { template: '<div class="v-row"><slot /></div>' },
    'v-col': { template: '<div class="v-col"><slot /></div>' },
    'v-card': { template: '<div class="v-card"><slot /></div>' },
    'v-card-text': { template: '<div class="v-card-text"><slot /></div>' },
    'v-form': {
      template: '<form class="v-form" @submit.prevent="handleSubmit"><slot /></form>',
      methods: {
        handleSubmit(e: Event) {
          e.preventDefault()
          e.stopPropagation()
          // In Vue 3, event handlers are in $attrs with 'on' prefix
          const onSubmit = this.$attrs.onSubmit || (this.$attrs as { onSubmit?: (e: Event) => void }).onSubmit
          if (onSubmit && typeof onSubmit === 'function') {
            onSubmit(e)
          }
        }
      }
    },
    'v-text-field': {
      template: `
        <input 
          :name="name" 
          :type="type || 'text'" 
          :value="modelValue" 
          @input="handleInput"
          class="v-text-field" 
        />
      `,
      props: {
        name: String,
        type: String,
        modelValue: [String, Number],
      },
      emits: ['update:modelValue'],
      setup(props: { name?: string; type?: string; modelValue?: string | number }, { emit }: { emit: (event: string, value: unknown) => void }) {
        const handleInput = (event: Event) => {
          const target = event.target as HTMLInputElement
          emit('update:modelValue', target.value)
        }
        return { handleInput }
      },
    },
    'v-btn': {
      template: '<button :type="type" :disabled="disabled" class="v-btn" @click="$emit(\'click\')"><slot /></button>',
      props: {
        type: String,
        disabled: Boolean,
      },
      emits: ['click'],
    },
    'v-alert': { template: '<div class="v-alert"><slot /></div>' },
    'v-icon': { template: '<i class="v-icon" />' },
  }

  return {
    global: {
      plugins: [
        router,
        [VueQueryPlugin, { queryClient }], // Pass queryClient to plugin - it will provide VUE_QUERY_CLIENT automatically
      ],
      provide: {
        ...options?.global?.provide,
      },
      stubs: {
        ...defaultStubs,
        ...options?.global?.stubs,
      },
      ...options?.global,
    },
    ...options,
  }
}

/**
 * Mocks axios instances (apiAxios and webAxios)
 */
export function mockAxios() {
  const mockGet = vi.fn()
  const mockPost = vi.fn()

  const apiAxiosMock = {
    get: mockGet,
    post: mockPost,
    interceptors: {
      response: {
        use: vi.fn(),
      },
    },
  }

  const webAxiosMock = {
    get: mockGet,
    post: mockPost,
  }

  vi.mock('@/lib/axios', () => ({
    default: apiAxiosMock,
    webAxios: webAxiosMock,
  }))

  return {
    apiAxios: apiAxiosMock,
    webAxios: webAxiosMock,
    mockGet,
    mockPost,
  }
}

/**
 * Mocks Vue Router
 */
export function mockRouter() {
  const push = vi.fn().mockResolvedValue(undefined)
  const replace = vi.fn().mockResolvedValue(undefined)

  vi.mock('vue-router', async () => {
    const actual = await vi.importActual('vue-router')
    return {
      ...actual,
      useRouter: () => ({
        push,
        replace,
        currentRoute: {
          value: { path: '/' },
        },
      }),
      useRoute: () => ({
        path: '/',
        params: {},
        query: {},
      }),
    }
  })

  return { push, replace }
}

/**
 * Mocks Pinia auth store
 */
export function mockAuthStore() {
  const login = vi.fn()
  const register = vi.fn()
  const logout = vi.fn()
  const fetchUser = vi.fn()

  const mockStore = {
    user: null,
    isAuthenticated: false,
    isAdmin: false,
    isEmployee: false,
    isLoadingUser: false,
    isLoginLoading: false,
    isRegisterLoading: false,
    isLogoutLoading: false,
    loginError: null,
    registerError: null,
    login,
    register,
    logout,
    fetchUser,
  }

  vi.mock('@/stores/auth', () => ({
    useAuthStore: () => mockStore,
  }))

  return mockStore
}

/**
 * Mocks Vue Query composables
 */
export function mockVueQuery() {
  const queryClient = {
    setQueryData: vi.fn(),
    getQueryData: vi.fn(),
    invalidateQueries: vi.fn(),
    removeQueries: vi.fn(),
    cancelQueries: vi.fn(),
    resetQueries: vi.fn(),
  }

  vi.mock('@tanstack/vue-query', async () => {
    const actual = await vi.importActual('@tanstack/vue-query')
    return {
      ...actual,
      useMutation: vi.fn((options: unknown) => {
        const opts = options as { mutationFn?: unknown; onSuccess?: unknown }
        return {
          mutateAsync: vi.fn(async (...args: unknown[]) => {
            if (opts.mutationFn) {
              const result = await (opts.mutationFn as (...args: unknown[]) => Promise<unknown>)(...args)
              if (opts.onSuccess) {
                ;(opts.onSuccess as (data: unknown) => void)(result)
              }
              return result
            }
            return Promise.resolve({})
          }),
          isPending: { value: false },
          error: { value: null },
        }
      }),
      useQuery: vi.fn(() => ({
        data: { value: null },
        isLoading: { value: false },
        error: { value: null },
        refetch: vi.fn(),
      })),
      useQueryClient: () => queryClient,
    }
  })

  return { queryClient }
}

/**
 * Mocks notification composable
 */
export function mockNotification() {
  const showSuccess = vi.fn()
  const showError = vi.fn()
  const showWarning = vi.fn()
  const showInfo = vi.fn()

  vi.mock('@/composables/useNotification', () => ({
    useNotification: () => ({
      showSuccess,
      showError,
      showWarning,
      showInfo,
      state: { value: { show: false, message: '', type: 'success' } },
    }),
  }))

  return { showSuccess, showError, showWarning, showInfo }
}

/**
 * Waits for next tick (Vue reactivity update)
 */
export async function waitForNextTick() {
  await new Promise((resolve) => setTimeout(resolve, 0))
}

/**
 * Waits for a specified number of milliseconds
 */
export function waitFor(ms: number) {
  return new Promise((resolve) => setTimeout(resolve, ms))
}
