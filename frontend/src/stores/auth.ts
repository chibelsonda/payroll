import { defineStore } from 'pinia'
import { computed } from 'vue'
import { useLogin, useRegister, useLogout, useCurrentUser } from '@/composables/useAuth'
import type { LoginCredentials, RegisterData } from '@/types/auth'

export const useAuthStore = defineStore('auth', () => {
  const loginMutation = useLogin()
  const registerMutation = useRegister()
  const logoutMutation = useLogout()
  const { data: user, refetch: refetchUser, isLoading: isLoadingUser, enable } = useCurrentUser()

  const isAuthenticated = computed(() => !!user.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isEmployee = computed(() => user.value?.role === 'employee')

  const login = async (credentials: LoginCredentials) => {
    return await loginMutation.mutateAsync(credentials)
  }

  const register = async (data: RegisterData) => {
    return await registerMutation.mutateAsync(data)
  }

  const logout = async () => {
    await logoutMutation.mutateAsync()
  }

  const fetchUser = async () => {
    enable()
    try {
      await refetchUser()
    } catch {
      // Silently ignore 401 errors (user is not authenticated)
    }
  }

  return {
    user,
    isAuthenticated,
    isAdmin,
    isEmployee,
    isLoadingUser,
    login,
    register,
    logout,
    fetchUser,
    isLoginLoading: computed(() => loginMutation.isPending.value),
    isRegisterLoading: computed(() => registerMutation.isPending.value),
    isLogoutLoading: computed(() => logoutMutation.isPending.value),
    loginError: computed(() => loginMutation.error.value),
    registerError: computed(() => registerMutation.error.value),
  }
})
