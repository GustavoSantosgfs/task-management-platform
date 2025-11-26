import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { requiresAuth: false, layout: 'blank' }
  },
  {
    path: '/',
    name: 'dashboard',
    redirect: '/projects'
  },
  {
    path: '/projects',
    name: 'projects',
    component: () => import('@/views/ProjectsView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/projects/:id',
    name: 'project-detail',
    component: () => import('@/views/ProjectDetailView.vue'),
    meta: { requiresAuth: true },
    props: true
  },
  {
    path: '/my-tasks',
    name: 'my-tasks',
    component: () => import('@/views/MyTasksView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    redirect: '/projects'
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// Navigation guard for authentication
router.beforeEach((to, _from, next) => {
  const token = localStorage.getItem('auth_token')
  const requiresAuth = to.meta.requiresAuth !== false

  if (requiresAuth && !token) {
    next({ name: 'login', query: { redirect: to.fullPath } })
  } else if (to.name === 'login' && token) {
    next({ name: 'projects' })
  } else {
    next()
  }
})

export default router
