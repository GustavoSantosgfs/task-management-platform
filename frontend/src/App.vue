<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '@/components/layout/AppLayout.vue'

const route = useRoute()
const router = useRouter()
const isRouterReady = ref(false)

onMounted(() => {
  router.isReady().then(() => {
    isRouterReady.value = true
  })
})

const useBlankLayout = computed(() => route.meta.layout === 'blank')
</script>

<template>
  <template v-if="!isRouterReady">
    <div class="d-flex justify-content-center align-items-center min-vh-100">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
  </template>
  <template v-else-if="useBlankLayout">
    <router-view />
  </template>
  <template v-else>
    <AppLayout>
      <router-view />
    </AppLayout>
  </template>
</template>

<style>
#app {
  min-height: 100vh;
}
</style>
