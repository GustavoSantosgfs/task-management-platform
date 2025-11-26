import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth'
import type { User, LoginCredentials } from '@/types'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isProjectManager = computed(() => user.value?.role === 'project_manager' || user.value?.role === 'admin')
  const isMember = computed(() => user.value?.role === 'member')

  function login(credentials: LoginCredentials): Promise<void> {
    loading.value = true
    error.value = null

    return authApi.login(credentials)
      .then((response) => {
        if (response.success) {
          token.value = response.data.token
          user.value = response.data.user
          localStorage.setItem('auth_token', response.data.token)
          localStorage.setItem('auth_user', JSON.stringify(response.data.user))
          const redirect = router.currentRoute.value.query.redirect as string
          router.push(redirect || '/projects')
        }
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Login failed'
        return Promise.reject(err)
      })
      .finally(() => {
        loading.value = false
      })
  }

  function logout(): void {
    loading.value = true

    authApi.logout()
      .catch(() => {
        // Ignore logout errors
      })
      .finally(() => {
        token.value = null
        user.value = null
        localStorage.removeItem('auth_token')
        localStorage.removeItem('auth_user')
        loading.value = false
        router.push('/login')
      })
  }

  function fetchUser(): Promise<void> {
    if (!token.value) return Promise.resolve()

    loading.value = true

    return authApi.me()
      .then((response) => {
        if (response.success) {
          user.value = response.data
          localStorage.setItem('auth_user', JSON.stringify(response.data))
        }
      })
      .catch(() => {
        token.value = null
        user.value = null
        localStorage.removeItem('auth_token')
        localStorage.removeItem('auth_user')
      })
      .finally(() => {
        loading.value = false
      })
  }

  function initializeFromStorage(): void {
    const storedUser = localStorage.getItem('auth_user')
    if (storedUser) {
      const parsed = JSON.parse(storedUser)
      if (parsed) {
        user.value = parsed
      }
    }
  }

  function canManageProject(managerId?: number): boolean {
    if (!user.value) return false
    if (user.value.role === 'admin') return true
    if (user.value.role === 'project_manager') {
      return managerId === user.value.id || managerId === undefined
    }
    return false
  }

  function canUpdateTask(assigneeId?: number): boolean {
    if (!user.value) return false
    if (user.value.role === 'admin') return true
    if (user.value.role === 'project_manager') return true
    return assigneeId === user.value.id
  }

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    isAdmin,
    isProjectManager,
    isMember,
    login,
    logout,
    fetchUser,
    initializeFromStorage,
    canManageProject,
    canUpdateTask
  }
})
