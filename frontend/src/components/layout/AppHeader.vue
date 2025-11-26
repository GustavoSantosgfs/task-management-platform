<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import NotificationsDropdown from './NotificationsDropdown.vue'

const authStore = useAuthStore()

const userName = computed(() => authStore.user?.name || 'User')
const userRole = computed(() => {
  const role = authStore.user?.role
  if (role === 'admin') return 'Organization Admin'
  if (role === 'project_manager') return 'Project Manager'
  return 'Member'
})

function handleLogout(): void {
  authStore.logout()
}
</script>

<template>
  <header class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
      <router-link to="/projects" class="navbar-brand fw-bold">
        TaskFlow
      </router-link>

      <button
        class="navbar-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#navbarNav"
        aria-controls="navbarNav"
        aria-expanded="false"
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <router-link to="/projects" class="nav-link" active-class="active">
              Projects
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/my-tasks" class="nav-link" active-class="active">
              My Tasks
            </router-link>
          </li>
        </ul>

        <ul class="navbar-nav align-items-center">
          <li class="nav-item me-3">
            <NotificationsDropdown />
          </li>
          <li class="nav-item dropdown">
            <a
              class="nav-link dropdown-toggle d-flex align-items-center"
              href="#"
              id="userDropdown"
              role="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              <div class="avatar-circle me-2">
                {{ userName.charAt(0).toUpperCase() }}
              </div>
              <span class="d-none d-md-inline">{{ userName }}</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li class="dropdown-header">
                <strong>{{ userName }}</strong>
                <br>
                <small class="text-muted">{{ userRole }}</small>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <button class="dropdown-item text-danger" @click="handleLogout">
                  Logout
                </button>
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </header>
</template>

<style scoped>
.avatar-circle {
  width: 32px;
  height: 32px;
  background-color: #6c757d;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  color: white;
  font-size: 14px;
}

.navbar-brand {
  font-size: 1.25rem;
}
</style>
