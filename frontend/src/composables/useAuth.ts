import { ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios, { webAxios } from '@/lib/axios'
import type { User, LoginCredentials, RegisterData } from '@/types/auth'

// Fetch current user from API
const fetchCurrentUser = async (): Promise<User | null> => {
  //try {
    const response = await axios.get('/user')
  return response.data.data
  // } catch (error: unknown) {
  //   // Return null if unauthorized (401) - user is not authenticated
  //   const axiosError = error as { response?: { status?: number }; config?: { url?: string } }
  //   if (axiosError?.response?.status === 401 && axiosError?.config?.url?.includes('/user')) {
  //     // Suppress console.error for this specific 401
  //     return null
  //   }
  //   throw error
  // }
}

// Login: get CSRF cookie, then login, then fetch user
const loginUser = async (credentials: LoginCredentials): Promise<User> => {
  await webAxios.get('/sanctum/csrf-cookie')
  await webAxios.post('/login', credentials)
  const user = await fetchCurrentUser()
  if (!user) {
    throw new Error('Failed to fetch user after login')
  }
  return user
}

// Register: get CSRF cookie, then register, and use the returned user data
const registerUser = async (data: RegisterData): Promise<User> => {
  await webAxios.get('/sanctum/csrf-cookie')
  const response = await webAxios.post('/register', data)
  // The register endpoint returns the user data directly, use it instead of fetching
  const user = response.data.data?.user
  if (!user) {
    throw new Error('Failed to get user from registration response')
  }
  return user
}

// Logout
const logoutUser = async (): Promise<void> => {
  await webAxios.post('/logout')
}

// Composables
export const useLogin = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: loginUser,
    onSuccess: (user) => {
      queryClient.setQueryData(['user'], user)
    },
  })
}

export const useRegister = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: registerUser,
    onSuccess: (user) => {
      queryClient.setQueryData(['user'], user)
    },
  })
}

export const useLogout = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: logoutUser,
    onMutate: async () => {
      // Clear user immediately to disable all authenticated queries
      queryClient.setQueryData(['user'], null)
      // Cancel all in-flight queries before logout to prevent 401 errors
      await queryClient.cancelQueries()
    },
    onSuccess: () => {
      // Remove all queries (don't invalidate - that would trigger refetch during logout)
      queryClient.removeQueries()
      // Reset all query cache to ensure clean state
      queryClient.resetQueries()
    },
  })
}

export const useCurrentUser = () => {
  const isEnabled = ref(false)

  const query = useQuery({
    queryKey: ['user'],
    queryFn: fetchCurrentUser,
    retry: false,
    staleTime: 1000 * 60 * 10,
    gcTime: 1000 * 60 * 60 * 24,
    enabled: isEnabled,
  })

  return {
    ...query,
    enable: () => { isEnabled.value = true },
  }
}
