import apiClient from './client'
import type {
  ApiResponse,
  PaginatedResponse,
  Project,
  ProjectFilters,
  CreateProjectData,
  UpdateProjectData,
  User,
} from '@/types'

export const projectsApi = {
  async getProjects(filters: ProjectFilters = {}): Promise<PaginatedResponse<Project>> {
    const response = await apiClient.get('/projects', { params: filters })
    return response.data
  },

  async getProject(id: number): Promise<ApiResponse<Project>> {
    const response = await apiClient.get(`/projects/${id}`)
    return response.data
  },

  async createProject(data: CreateProjectData): Promise<ApiResponse<Project>> {
    const response = await apiClient.post('/projects', data)
    return response.data
  },

  async updateProject(id: number, data: UpdateProjectData): Promise<ApiResponse<Project>> {
    const response = await apiClient.put(`/projects/${id}`, data)
    return response.data
  },

  async deleteProject(id: number): Promise<ApiResponse<null>> {
    const response = await apiClient.delete(`/projects/${id}`)
    return response.data
  },

  async restoreProject(id: number): Promise<ApiResponse<Project>> {
    const response = await apiClient.post(`/projects/${id}/restore`)
    return response.data
  },

  async getMembers(projectId: number): Promise<ApiResponse<User[]>> {
    const response = await apiClient.get(`/projects/${projectId}/members`)
    return response.data
  },

  async addMember(projectId: number, userId: number): Promise<ApiResponse<Project>> {
    const response = await apiClient.post(`/projects/${projectId}/members`, { user_id: userId })
    return response.data
  },

  async removeMember(projectId: number, userId: number): Promise<ApiResponse<Project>> {
    const response = await apiClient.delete(`/projects/${projectId}/members/${userId}`)
    return response.data
  },
}
