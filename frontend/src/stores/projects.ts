import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { projectsApi } from '@/api/projects'
import type { Project, ProjectFilters, CreateProjectData, UpdateProjectData } from '@/types'

export const useProjectsStore = defineStore('projects', () => {
  const projects = ref<Project[]>([])
  const currentProject = ref<Project | null>(null)
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

  const activeProjects = computed(() =>
    projects.value.filter(p => p.status === 'active')
  )

  const archivedProjects = computed(() =>
    projects.value.filter(p => p.deleted_at !== null)
  )

  function fetchProjects(filters: ProjectFilters = {}): Promise<void> {
    loading.value = true
    error.value = null

    return projectsApi.getProjects(filters)
      .then((response) => {
        if (response.success) {
          projects.value = response.data
          meta.value = response.meta
        }
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to fetch projects'
        return Promise.reject(err)
      })
      .finally(() => {
        loading.value = false
      })
  }

  function fetchProject(id: number): Promise<Project> {
    loading.value = true
    error.value = null

    return projectsApi.getProject(id)
      .then((response) => {
        if (response.success) {
          currentProject.value = response.data
          return response.data
        }
        return Promise.reject(new Error('Failed to fetch project'))
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to fetch project'
        return Promise.reject(err)
      })
      .finally(() => {
        loading.value = false
      })
  }

  function createProject(data: CreateProjectData): Promise<Project> {
    loading.value = true
    error.value = null

    return projectsApi.createProject(data)
      .then((response) => {
        if (response.success) {
          projects.value.unshift(response.data)
          return response.data
        }
        return Promise.reject(new Error('Failed to create project'))
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to create project'
        return Promise.reject(err)
      })
      .finally(() => {
        loading.value = false
      })
  }

  function updateProject(id: number, data: UpdateProjectData): Promise<Project> {
    loading.value = true
    error.value = null

    return projectsApi.updateProject(id, data)
      .then((response) => {
        if (response.success) {
          const index = projects.value.findIndex(p => p.id === id)
          if (index !== -1) {
            projects.value[index] = response.data
          }
          if (currentProject.value?.id === id) {
            currentProject.value = response.data
          }
          return response.data
        }
        return Promise.reject(new Error('Failed to update project'))
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to update project'
        return Promise.reject(err)
      })
      .finally(() => {
        loading.value = false
      })
  }

  function deleteProject(id: number): Promise<void> {
    loading.value = true
    error.value = null

    return projectsApi.deleteProject(id)
      .then((response) => {
        if (response.success) {
          projects.value = projects.value.filter(p => p.id !== id)
          if (currentProject.value?.id === id) {
            currentProject.value = null
          }
        }
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to delete project'
        return Promise.reject(err)
      })
      .finally(() => {
        loading.value = false
      })
  }

  function restoreProject(id: number): Promise<Project> {
    loading.value = true
    error.value = null

    return projectsApi.restoreProject(id)
      .then((response) => {
        if (response.success) {
          const index = projects.value.findIndex(p => p.id === id)
          if (index !== -1) {
            projects.value[index] = response.data
          }
          return response.data
        }
        return Promise.reject(new Error('Failed to restore project'))
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to restore project'
        return Promise.reject(err)
      })
      .finally(() => {
        loading.value = false
      })
  }

  function addMember(projectId: number, userId: number): Promise<Project> {
    loading.value = true
    error.value = null

    return projectsApi.addMember(projectId, userId)
      .then((response) => {
        if (response.success) {
          if (currentProject.value?.id === projectId) {
            currentProject.value = response.data
          }
          return response.data
        }
        return Promise.reject(new Error('Failed to add member'))
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to add member'
        return Promise.reject(err)
      })
      .finally(() => {
        loading.value = false
      })
  }

  function removeMember(projectId: number, userId: number): Promise<Project> {
    loading.value = true
    error.value = null

    return projectsApi.removeMember(projectId, userId)
      .then((response) => {
        if (response.success) {
          if (currentProject.value?.id === projectId) {
            currentProject.value = response.data
          }
          return response.data
        }
        return Promise.reject(new Error('Failed to remove member'))
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to remove member'
        return Promise.reject(err)
      })
      .finally(() => {
        loading.value = false
      })
  }

  function clearCurrentProject(): void {
    currentProject.value = null
  }

  return {
    projects,
    currentProject,
    loading,
    error,
    meta,
    activeProjects,
    archivedProjects,
    fetchProjects,
    fetchProject,
    createProject,
    updateProject,
    deleteProject,
    restoreProject,
    addMember,
    removeMember,
    clearCurrentProject
  }
})
