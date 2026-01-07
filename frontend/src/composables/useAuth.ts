import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from '@/lib/axios'
import type { User, LoginCredentials, RegisterData } from '@/types/auth'

// Fetch current user from API
const fetchCurrentUser = async (): Promise<User> => {
  const response = await axios.get('/api/v1/user')
  return response.data.data
}

// Login: get CSRF cookie, then login, then fetch user
const loginUser = async (credentials: LoginCredentials): Promise<User> => {
  await axios.get('/sanctum/csrf-cookie')
  await axios.post('/login', credentials)
  return await fetchCurrentUser()
}

// Register: get CSRF cookie, then register, then fetch user
const registerUser = async (data: RegisterData): Promise<User> => {
  await axios.get('/sanctum/csrf-cookie')
  await axios.post('/register', data)
  return await fetchCurrentUser()
}

// Logout
const logoutUser = async (): Promise<void> => {
  await axios.post('/logout')
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
    onSuccess: () => {
      queryClient.removeQueries({ queryKey: ['user'] })
    },
  })
}

export const useCurrentUser = () => {
  return useQuery({
    queryKey: ['user'],
    queryFn: fetchCurrentUser,
    retry: false,
    staleTime: 1000 * 60 * 10,
    gcTime: 1000 * 60 * 60 * 24,
  })
}
