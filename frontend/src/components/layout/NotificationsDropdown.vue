<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useNotificationsStore } from '@/stores/notifications'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import type { Notification } from '@/types'

const notificationsStore = useNotificationsStore()
const authStore = useAuthStore()
const router = useRouter()

const isOpen = ref(false)
let stopPolling: (() => void) | null = null

const recentNotifications = computed(() =>
  notificationsStore.notifications.slice(0, 5)
)

onMounted(() => {
  if (authStore.isAuthenticated) {
    notificationsStore.fetchNotifications({ per_page: 10 })
    stopPolling = notificationsStore.startPolling(30000)
  }
})

onUnmounted(() => {
  if (stopPolling) {
    stopPolling()
  }
})

function toggleDropdown(): void {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    notificationsStore.fetchNotifications({ per_page: 10 })
  }
}

function handleNotificationClick(notification: Notification): void {
  notificationsStore.markAsRead(notification.id)
  isOpen.value = false

  const data = notification.data as { project_id?: number; task_id?: number }
  if (data.project_id && data.task_id) {
    router.push(`/projects/${data.project_id}?task=${data.task_id}`)
  } else if (data.project_id) {
    router.push(`/projects/${data.project_id}`)
  }
}

function handleMarkAllRead(): void {
  notificationsStore.markAllAsRead()
}

function formatTime(dateString: string): string {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays < 7) return `${diffDays}d ago`
  return date.toLocaleDateString()
}

function getNotificationIcon(type: string): string {
  const icons: Record<string, string> = {
    task_assigned: 'bi-person-plus',
    task_comment: 'bi-chat',
    task_status_changed: 'bi-arrow-repeat',
    mention: 'bi-at',
    project_invite: 'bi-folder-plus',
    task_due_soon: 'bi-clock'
  }
  return icons[type] || 'bi-bell'
}
</script>

<template>
  <div class="dropdown">
    <button
      class="btn btn-link nav-link position-relative p-0"
      type="button"
      @click="toggleDropdown"
      aria-expanded="false"
    >
      <i class="bi bi-bell fs-5"></i>
      <span
        v-if="notificationsStore.unreadCount > 0"
        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
      >
        {{ notificationsStore.unreadCount > 99 ? '99+' : notificationsStore.unreadCount }}
      </span>
    </button>

    <div
      class="dropdown-menu dropdown-menu-end notifications-dropdown"
      :class="{ show: isOpen }"
    >
      <div class="dropdown-header d-flex justify-content-between align-items-center">
        <span class="fw-bold">Notifications</span>
        <button
          v-if="notificationsStore.unreadCount > 0"
          class="btn btn-link btn-sm p-0 text-decoration-none"
          @click="handleMarkAllRead"
        >
          Mark all read
        </button>
      </div>

      <div class="notifications-list">
        <template v-if="recentNotifications.length > 0">
          <button
            v-for="notification in recentNotifications"
            :key="notification.id"
            class="dropdown-item notification-item"
            :class="{ unread: !notification.is_read }"
            @click="handleNotificationClick(notification)"
          >
            <div class="d-flex align-items-start">
              <div class="notification-icon me-2">
                <i :class="['bi', getNotificationIcon(notification.type)]"></i>
              </div>
              <div class="notification-content flex-grow-1">
                <div class="notification-title">{{ notification.title }}</div>
                <div class="notification-message text-muted small">
                  {{ notification.message }}
                </div>
                <div class="notification-time text-muted small">
                  {{ formatTime(notification.created_at) }}
                </div>
              </div>
              <div v-if="!notification.is_read" class="unread-dot"></div>
            </div>
          </button>
        </template>
        <div v-else class="dropdown-item text-center text-muted py-4">
          No notifications
        </div>
      </div>
    </div>

    <div
      v-if="isOpen"
      class="dropdown-backdrop"
      @click="isOpen = false"
    ></div>
  </div>
</template>

<style scoped>
.notifications-dropdown {
  width: 360px;
  max-height: 480px;
  overflow: hidden;
}

.notifications-list {
  max-height: 400px;
  overflow-y: auto;
}

.notification-item {
  white-space: normal;
  padding: 12px 16px;
  border-bottom: 1px solid #e9ecef;
}

.notification-item:last-child {
  border-bottom: none;
}

.notification-item.unread {
  background-color: #f8f9fa;
}

.notification-item:hover {
  background-color: #e9ecef;
}

.notification-icon {
  width: 32px;
  height: 32px;
  background-color: #e9ecef;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.notification-title {
  font-weight: 500;
  font-size: 14px;
  margin-bottom: 2px;
}

.notification-message {
  font-size: 13px;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.notification-time {
  font-size: 12px;
  margin-top: 4px;
}

.unread-dot {
  width: 8px;
  height: 8px;
  background-color: #0d6efd;
  border-radius: 50%;
  flex-shrink: 0;
  margin-top: 4px;
}

.dropdown-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1000;
}

.dropdown-menu.show {
  display: block;
  z-index: 1001;
}
</style>
