import apiClient from './client'
import type {
  ApiResponse,
  PaginatedResponse,
  Task,
  TaskFilters,
  CreateTaskData,
  UpdateTaskData,
  TaskComment,
} from '@/types'

export const tasksApi = {
  // Tasks
  async getTasks(projectId: number, filters: TaskFilters = {}): Promise<PaginatedResponse<Task>> {
    const response = await apiClient.get(`/projects/${projectId}/tasks`, { params: filters })
    return response.data
  },

  async getMyTasks(filters: TaskFilters = {}): Promise<PaginatedResponse<Task>> {
    const response = await apiClient.get('/my-tasks', { params: filters })
    return response.data
  },

  async getTask(projectId: number, taskId: number): Promise<ApiResponse<Task>> {
    const response = await apiClient.get(`/projects/${projectId}/tasks/${taskId}`)
    return response.data
  },

  async createTask(projectId: number, data: CreateTaskData): Promise<ApiResponse<Task>> {
    const response = await apiClient.post(`/projects/${projectId}/tasks`, data)
    return response.data
  },

  async updateTask(projectId: number, taskId: number, data: UpdateTaskData): Promise<ApiResponse<Task>> {
    const response = await apiClient.put(`/projects/${projectId}/tasks/${taskId}`, data)
    return response.data
  },

  async deleteTask(projectId: number, taskId: number): Promise<ApiResponse<null>> {
    const response = await apiClient.delete(`/projects/${projectId}/tasks/${taskId}`)
    return response.data
  },

  async restoreTask(projectId: number, taskId: number): Promise<ApiResponse<Task>> {
    const response = await apiClient.post(`/projects/${projectId}/tasks/${taskId}/restore`)
    return response.data
  },

  // Dependencies
  async getDependencies(projectId: number, taskId: number): Promise<ApiResponse<Task[]>> {
    const response = await apiClient.get(`/projects/${projectId}/tasks/${taskId}/dependencies`)
    return response.data
  },

  async addDependency(projectId: number, taskId: number, dependsOnTaskId: number): Promise<ApiResponse<Task>> {
    const response = await apiClient.post(`/projects/${projectId}/tasks/${taskId}/dependencies`, {
      depends_on_task_id: dependsOnTaskId,
    })
    return response.data
  },

  async removeDependency(projectId: number, taskId: number, dependencyId: number): Promise<ApiResponse<Task>> {
    const response = await apiClient.delete(`/projects/${projectId}/tasks/${taskId}/dependencies/${dependencyId}`)
    return response.data
  },

  // Comments
  async getComments(projectId: number, taskId: number): Promise<ApiResponse<TaskComment[]>> {
    const response = await apiClient.get(`/projects/${projectId}/tasks/${taskId}/comments`)
    return response.data
  },

  async addComment(
    projectId: number,
    taskId: number,
    content: string,
    mentions?: number[]
  ): Promise<ApiResponse<TaskComment>> {
    const response = await apiClient.post(`/projects/${projectId}/tasks/${taskId}/comments`, {
      content,
      mentions,
    })
    return response.data
  },

  async updateComment(
    projectId: number,
    taskId: number,
    commentId: number,
    content: string,
    mentions?: number[]
  ): Promise<ApiResponse<TaskComment>> {
    const response = await apiClient.put(`/projects/${projectId}/tasks/${taskId}/comments/${commentId}`, {
      content,
      mentions,
    })
    return response.data
  },

  async deleteComment(projectId: number, taskId: number, commentId: number): Promise<ApiResponse<null>> {
    const response = await apiClient.delete(`/projects/${projectId}/tasks/${taskId}/comments/${commentId}`)
    return response.data
  },
}
