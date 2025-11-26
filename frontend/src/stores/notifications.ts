import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { notificationsApi } from '@/api/notifications'
import type { Notification, NotificationFilters } from '@/types'

export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref<Notification[]>([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
    from: 0,
    to: 0
  })

  const unreadNotifications = computed(() =>
    notifications.value.filter(n => !n.is_read)
  )

  const readNotifications = computed(() =>
    notifications.value.filter(n => n.is_read)
  )

  function fetchNotifications(filters: NotificationFilters = {}): Promise<void> {
    loading.value = true
    error.value = null

    return notificationsApi.getNotifications(filters)
      .then((response) => {
        if (response.success) {
          notifications.value = response.data
          meta.value = response.meta
        }
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to fetch notifications'
        return Promise.reject(err)
      })
      .finally(() => {
        loading.value = false
      })
  }

  function fetchUnreadCount(): Promise<void> {
    return notificationsApi.getUnreadCount()
      .then((response) => {
        if (response.success) {
          unreadCount.value = response.data.unread_count
        }
      })
      .catch(() => {
        // Silently fail for count fetch
      })
  }

  function markAsRead(id: number): Promise<Notification> {
    return notificationsApi.markAsRead(id)
      .then((response) => {
        if (response.success) {
          const index = notifications.value.findIndex(n => n.id === id)
          if (index !== -1) {
            notifications.value[index] = response.data
          }
          unreadCount.value = Math.max(0, unreadCount.value - 1)
          return response.data
        }
        return Promise.reject(new Error('Failed to mark as read'))
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to mark as read'
        return Promise.reject(err)
      })
  }

  function markAsUnread(id: number): Promise<Notification> {
    return notificationsApi.markAsUnread(id)
      .then((response) => {
        if (response.success) {
          const index = notifications.value.findIndex(n => n.id === id)
          if (index !== -1) {
            notifications.value[index] = response.data
          }
          unreadCount.value += 1
          return response.data
        }
        return Promise.reject(new Error('Failed to mark as unread'))
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to mark as unread'
        return Promise.reject(err)
      })
  }

  function markAllAsRead(): Promise<void> {
    return notificationsApi.markAllAsRead()
      .then((response) => {
        if (response.success) {
          notifications.value.forEach(n => {
            n.is_read = true
            n.read_at = new Date().toISOString()
          })
          unreadCount.value = 0
        }
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to mark all as read'
        return Promise.reject(err)
      })
  }

  function deleteNotification(id: number): Promise<void> {
    return notificationsApi.deleteNotification(id)
      .then((response) => {
        if (response.success) {
          const notification = notifications.value.find(n => n.id === id)
          if (notification && !notification.is_read) {
            unreadCount.value = Math.max(0, unreadCount.value - 1)
          }
          notifications.value = notifications.value.filter(n => n.id !== id)
        }
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to delete notification'
        return Promise.reject(err)
      })
  }

  function deleteAllRead(): Promise<void> {
    return notificationsApi.deleteAllRead()
      .then((response) => {
        if (response.success) {
          notifications.value = notifications.value.filter(n => !n.is_read)
        }
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to delete read notifications'
        return Promise.reject(err)
      })
  }

  function startPolling(intervalMs: number = 30000): () => void {
    fetchUnreadCount()
    const intervalId = setInterval(() => {
      fetchUnreadCount()
    }, intervalMs)

    return () => clearInterval(intervalId)
  }

  return {
    notifications,
    unreadCount,
    loading,
    error,
    meta,
    unreadNotifications,
    readNotifications,
    fetchNotifications,
    fetchUnreadCount,
    markAsRead,
    markAsUnread,
    markAllAsRead,
    deleteNotification,
    deleteAllRead,
    startPolling
  }
})
