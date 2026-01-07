import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from 'axios'
import type { User, LoginCredentials, RegisterData } from '@/types/auth'

// API functions
const loginUser = async (credentials: LoginCredentials): Promise<{ user: User; token: string }> => {
  const response = await axios.post('/api/v1/login', credentials)
  return response.data.data
}

const registerUser = async (data: RegisterData): Promise<{ user: User; token: string }> => {
  const response = await axios.post('/api/v1/register', data)
  return response.data.data
}

const logoutUser = async (): Promise<void> => {
  await axios.post('/api/v1/logout')
}

const fetchCurrentUser = async (): Promise<User> => {
  const response = await axios.get('/api/v1/user')
  return response.data.data
}

// Composables
export const useLogin = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: loginUser,
    onSuccess: (data) => {
      // Store token
      localStorage.setItem('token', data.token)
      // Update user in cache
      queryClient.setQueryData(['user'], data.user)
    },
  })
}

export const useRegister = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: registerUser,
    onSuccess: (data) => {
      // Store token
      localStorage.setItem('token', data.token)
      // Update user in cache
      queryClient.setQueryData(['user'], data.user)
    },
  })
}

export const useLogout = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: logoutUser,
    onSuccess: () => {
      // Clear token
      localStorage.removeItem('token')
      // Clear persisted query cache from localStorage
      localStorage.removeItem('ces-query-cache')
      // Clear user from cache
      queryClient.removeQueries({ queryKey: ['user'] })
      // Clear all cached data
      queryClient.clear()
    },
  })
}

export const useCurrentUser = () => {
  return useQuery({
    queryKey: ['user'],
    queryFn: fetchCurrentUser,
    retry: false,
    staleTime: 1000 * 60 * 10, // 10 minutes
    gcTime: 1000 * 60 * 60 * 24, // 24 hours for persistence
    enabled: !!localStorage.getItem('token'), // Only fetch if token exists
  })
}
