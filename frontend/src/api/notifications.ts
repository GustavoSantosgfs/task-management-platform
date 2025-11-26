import apiClient from './client'
import type { ApiResponse, PaginatedResponse, Notification, NotificationFilters } from '@/types'

export const notificationsApi = {
  async getNotifications(filters: NotificationFilters = {}): Promise<PaginatedResponse<Notification>> {
    const response = await apiClient.get('/notifications', { params: filters })
    return response.data
  },

  async getUnreadCount(): Promise<ApiResponse<{ unread_count: number }>> {
    const response = await apiClient.get('/notifications/unread-count')
    return response.data
  },

  async getNotification(id: number): Promise<ApiResponse<Notification>> {
    const response = await apiClient.get(`/notifications/${id}`)
    return response.data
  },

  async markAsRead(id: number): Promise<ApiResponse<Notification>> {
    const response = await apiClient.post(`/notifications/${id}/read`)
    return response.data
  },

  async markAsUnread(id: number): Promise<ApiResponse<Notification>> {
    const response = await apiClient.post(`/notifications/${id}/unread`)
    return response.data
  },

  async markAllAsRead(): Promise<ApiResponse<{ marked_count: number }>> {
    const response = await apiClient.post('/notifications/mark-all-read')
    return response.data
  },

  async deleteNotification(id: number): Promise<ApiResponse<null>> {
    const response = await apiClient.delete(`/notifications/${id}`)
    return response.data
  },

  async deleteAllRead(): Promise<ApiResponse<{ deleted_count: number }>> {
    const response = await apiClient.delete('/notifications/read')
    return response.data
  },
}
