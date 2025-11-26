<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { authApi } from '@/api/auth'

interface MockUser {
  email: string
  name: string
  role: string
}

const authStore = useAuthStore()

const mockUsers = ref<MockUser[]>([])
const selectedEmail = ref('')
const loading = ref(false)
const fetchingUsers = ref(false)
const errorMessage = ref('')

onMounted(() => {
  fetchMockUsers()
})

function fetchMockUsers(): void {
  fetchingUsers.value = true
  authApi.getMockUsers()
    .then((response) => {
      if (response.success && response.data) {
        mockUsers.value = response.data
        const firstUser = response.data[0]
        if (firstUser) {
          selectedEmail.value = firstUser.email
        }
      }
    })
    .catch(() => {
      errorMessage.value = 'Failed to load available users'
    })
    .finally(() => {
      fetchingUsers.value = false
    })
}

function handleLogin(): void {
  if (!selectedEmail.value) {
    errorMessage.value = 'Please select a user'
    return
  }

  loading.value = true
  errorMessage.value = ''

  authStore.login({ email: selectedEmail.value, password: 'password123' })
    .catch((err: unknown) => {
      const axiosError = err as { response?: { data?: { message?: string } } }
      errorMessage.value = axiosError.response?.data?.message || 'Login failed. Please try again.'
    })
    .finally(() => {
      loading.value = false
    })
}

function getRoleBadgeClass(role: string): string {
  const classes: Record<string, string> = {
    admin: 'bg-danger',
    project_manager: 'bg-primary',
    member: 'bg-secondary'
  }
  return classes[role] || 'bg-secondary'
}

function formatRole(role: string): string {
  const labels: Record<string, string> = {
    admin: 'Admin',
    project_manager: 'Project Manager',
    member: 'Member'
  }
  return labels[role] || role
}
</script>

<template>
  <div class="login-page">
    <div class="login-container">
      <div class="card shadow-lg">
        <div class="card-body p-5">
          <div class="text-center mb-4">
            <h1 class="h3 fw-bold text-dark">TaskFlow</h1>
            <p class="text-muted">Task Management Platform</p>
          </div>

          <div v-if="errorMessage" class="alert alert-danger" role="alert">
            {{ errorMessage }}
          </div>

          <form @submit.prevent="handleLogin">
            <div class="mb-4">
              <label for="userSelect" class="form-label fw-medium">
                Select User
              </label>

              <div v-if="fetchingUsers" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted mt-2">Loading users...</p>
              </div>

              <div v-else class="user-list">
                <div
                  v-for="user in mockUsers"
                  :key="user.email"
                  class="user-card"
                  :class="{ selected: selectedEmail === user.email }"
                  @click="selectedEmail = user.email"
                >
                  <div class="d-flex align-items-center">
                    <div class="user-avatar me-3">
                      {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="flex-grow-1">
                      <div class="fw-medium">{{ user.name }}</div>
                      <div class="text-muted small">{{ user.email }}</div>
                    </div>
                    <span
                      class="badge"
                      :class="getRoleBadgeClass(user.role)"
                    >
                      {{ formatRole(user.role) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <button
              type="submit"
              class="btn btn-primary w-100 py-2"
              :disabled="loading || !selectedEmail"
            >
              <span v-if="loading">
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                Signing in...
              </span>
              <span v-else>Sign In</span>
            </button>
          </form>

          <div class="mt-4 text-center">
            <small class="text-muted">
              This is a demo application with mocked authentication.
              <br>Select any user above to sign in.
            </small>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.login-container {
  width: 100%;
  max-width: 440px;
}

.card {
  border: none;
  border-radius: 16px;
}

.user-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.user-card {
  padding: 12px 16px;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.user-card:hover {
  border-color: #0d6efd;
  background-color: #f8f9fa;
}

.user-card.selected {
  border-color: #0d6efd;
  background-color: #e7f1ff;
}

.user-avatar {
  width: 40px;
  height: 40px;
  background-color: #6c757d;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  font-size: 16px;
}

.user-card.selected .user-avatar {
  background-color: #0d6efd;
}
</style>
