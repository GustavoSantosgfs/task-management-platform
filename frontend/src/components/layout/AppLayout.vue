<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AppHeader from './AppHeader.vue'

const authStore = useAuthStore()

onMounted(() => {
  authStore.initializeFromStorage()
  if (authStore.token && !authStore.user) {
    authStore.fetchUser()
  }
})
</script>

<template>
  <div class="app-layout">
    <AppHeader />
    <main class="main-content">
      <div class="container-fluid py-4">
        <slot />
      </div>
    </main>
  </div>
</template>

<style scoped>
.app-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background-color: #f8f9fa;
}

.main-content {
  flex: 1;
}
</style>
