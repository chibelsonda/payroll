import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useLogin, useRegister, useLogout, useCurrentUser } from '@/composables/useAuth'
import type { LoginCredentials, RegisterData } from '@/types/auth'

export const useAuthStore = defineStore('auth', () => {
  // Token is the only source of truth for authentication
  const token = ref<string | null>(localStorage.getItem('token'))

  // Vue Query hooks - single source of truth for user data
  const loginMutation = useLogin()
  const registerMutation = useRegister()
  const logoutMutation = useLogout()
  const { data: user, refetch: refetchUser } = useCurrentUser()

  // Computed properties derived from Vue Query data
  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isStudent = computed(() => user.value?.role === 'student')

  // Token management
  const setToken = (newToken: string | null) => {
    token.value = newToken
    if (newToken) {
      localStorage.setItem('token', newToken)
    } else {
      localStorage.removeItem('token')
    }
  }

  // Auth actions
  const login = async (credentials: LoginCredentials) => {
    const result = await loginMutation.mutateAsync(credentials)
    setToken(result.token)
    return result
  }

  const register = async (data: RegisterData) => {
    const result = await registerMutation.mutateAsync(data)
    setToken(result.token)
    return result
  }

  const logout = async () => {
    await logoutMutation.mutateAsync()
    setToken(null)
  }

  const fetchUser = async () => {
    if (!token.value) return
    try {
      await refetchUser()
    } catch (error) {
      // If user fetch fails, clear token
      setToken(null)
      throw error
    }
  }

  // Initialize user data if token exists
  if (token.value && !user.value) {
    fetchUser()
  }

  return {
    // State - user comes from Vue Query
    user,
    token,
    // Computed
    isAuthenticated,
    isAdmin,
    isStudent,
    // Actions
    login,
    register,
    logout,
    fetchUser,
    // Loading states from mutations
    isLoginLoading: computed(() => loginMutation.isPending.value),
    isRegisterLoading: computed(() => registerMutation.isPending.value),
    isLogoutLoading: computed(() => logoutMutation.isPending.value),
    // Error states
    loginError: computed(() => loginMutation.error.value),
    registerError: computed(() => registerMutation.error.value),
  }
})
