import apiClient from './client'
import type { ApiResponse, AuthResponse, LoginCredentials, User } from '@/types'

export const authApi = {
  async login(credentials: LoginCredentials): Promise<ApiResponse<AuthResponse>> {
    const response = await apiClient.post('/auth/login', credentials)
    return response.data
  },

  async logout(): Promise<ApiResponse<null>> {
    const response = await apiClient.post('/auth/logout')
    return response.data
  },

  async me(): Promise<ApiResponse<User>> {
    const response = await apiClient.get('/auth/me')
    return response.data
  },

  async getMockUsers(): Promise<ApiResponse<Array<{ email: string; name: string; role: string }>>> {
    const response = await apiClient.get('/auth/mock-users')
    return response.data
  },
}
