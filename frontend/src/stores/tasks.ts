import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { tasksApi } from '@/api/tasks'
import type { Task, TaskFilters, CreateTaskData, UpdateTaskData, TaskComment, TaskStatus } from '@/types'

export const useTasksStore = defineStore('tasks', () => {
  const tasks = ref<Task[]>([])
  const myTasks = ref<Task[]>([])
  const currentTask = ref<Task | null>(null)
  const comments = ref<TaskComment[]>([])
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

  const tasksByStatus = computed(() => {
    const grouped: Record<TaskStatus, Task[]> = {
      backlog: [],
      todo: [],
      in_progress: [],
      review: [],
      done: [],
      blocked: []
    }
    tasks.value.forEach(task => {
      if (grouped[task.status]) {
        grouped[task.status].push(task)
      }
    })
    return grouped
  })

  function fetchTasks(projectId: number, filters: TaskFilters = {}): Promise<void> {
    loading.value = true
    error.value = null

    return tasksApi.getTasks(projectId, filters)
      .then((response) => {
        if (response.success) {
          tasks.value = response.data
          meta.value = response.meta
        }
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to fetch tasks'
        return Promise.reject(err)
      })
      .finally(() => {
        loading.value = false
      })
  }

  function fetchMyTasks(filters: TaskFilters = {}): Promise<void> {
    loading.value = true
    error.value = null

    return tasksApi.getMyTasks(filters)
      .then((response) => {
        if (response.success) {
          myTasks.value = response.data
          meta.value = response.meta
        }
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to fetch tasks'
        return Promise.reject(err)
      })
      .finally(() => {
        loading.value = false
      })
  }

  function fetchTask(projectId: number, taskId: number): Promise<Task> {
    loading.value = true
    error.value = null

    return tasksApi.getTask(projectId, taskId)
      .then((response) => {
        if (response.success) {
          currentTask.value = response.data
          return response.data
        }
        return Promise.reject(new Error('Failed to fetch task'))
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to fetch task'
        return Promise.reject(err)
      })
      .finally(() => {
        loading.value = false
      })
  }

  function createTask(projectId: number, data: CreateTaskData): Promise<Task> {
    loading.value = true
    error.value = null

    return tasksApi.createTask(projectId, data)
      .then((response) => {
        if (response.success) {
          tasks.value.push(response.data)
          return response.data
        }
        return Promise.reject(new Error('Failed to create task'))
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to create task'
        return Promise.reject(err)
      })
      .finally(() => {
        loading.value = false
      })
  }

  function updateTask(projectId: number, taskId: number, data: UpdateTaskData): Promise<Task> {
    loading.value = true
    error.value = null

    return tasksApi.updateTask(projectId, taskId, data)
      .then((response) => {
        if (response.success) {
          const index = tasks.value.findIndex(t => t.id === taskId)
          if (index !== -1) {
            tasks.value[index] = response.data
          }
          const myIndex = myTasks.value.findIndex(t => t.id === taskId)
          if (myIndex !== -1) {
            myTasks.value[myIndex] = response.data
          }
          if (currentTask.value?.id === taskId) {
            currentTask.value = response.data
          }
          return response.data
        }
        return Promise.reject(new Error('Failed to update task'))
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to update task'
        return Promise.reject(err)
      })
      .finally(() => {
        loading.value = false
      })
  }

  function deleteTask(projectId: number, taskId: number): Promise<void> {
    loading.value = true
    error.value = null

    return tasksApi.deleteTask(projectId, taskId)
      .then((response) => {
        if (response.success) {
          tasks.value = tasks.value.filter(t => t.id !== taskId)
          myTasks.value = myTasks.value.filter(t => t.id !== taskId)
          if (currentTask.value?.id === taskId) {
            currentTask.value = null
          }
        }
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to delete task'
        return Promise.reject(err)
      })
      .finally(() => {
        loading.value = false
      })
  }

  function fetchComments(projectId: number, taskId: number): Promise<void> {
    return tasksApi.getComments(projectId, taskId)
      .then((response) => {
        if (response.success) {
          comments.value = response.data
        }
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to fetch comments'
        return Promise.reject(err)
      })
  }

  function addComment(projectId: number, taskId: number, content: string, mentions?: number[]): Promise<TaskComment> {
    return tasksApi.addComment(projectId, taskId, content, mentions)
      .then((response) => {
        if (response.success) {
          comments.value.push(response.data)
          return response.data
        }
        return Promise.reject(new Error('Failed to add comment'))
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to add comment'
        return Promise.reject(err)
      })
  }

  function deleteComment(projectId: number, taskId: number, commentId: number): Promise<void> {
    return tasksApi.deleteComment(projectId, taskId, commentId)
      .then((response) => {
        if (response.success) {
          comments.value = comments.value.filter(c => c.id !== commentId)
        }
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to delete comment'
        return Promise.reject(err)
      })
  }

  function addDependency(projectId: number, taskId: number, dependsOnTaskId: number): Promise<Task> {
    return tasksApi.addDependency(projectId, taskId, dependsOnTaskId)
      .then((response) => {
        if (response.success) {
          if (currentTask.value?.id === taskId) {
            currentTask.value = response.data
          }
          return response.data
        }
        return Promise.reject(new Error('Failed to add dependency'))
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to add dependency'
        return Promise.reject(err)
      })
  }

  function removeDependency(projectId: number, taskId: number, dependencyId: number): Promise<Task> {
    return tasksApi.removeDependency(projectId, taskId, dependencyId)
      .then((response) => {
        if (response.success) {
          if (currentTask.value?.id === taskId) {
            currentTask.value = response.data
          }
          return response.data
        }
        return Promise.reject(new Error('Failed to remove dependency'))
      })
      .catch((err: unknown) => {
        const axiosError = err as { response?: { data?: { message?: string } } }
        error.value = axiosError.response?.data?.message || 'Failed to remove dependency'
        return Promise.reject(err)
      })
  }

  function setCurrentTask(task: Task | null): void {
    currentTask.value = task
  }

  function clearTasks(): void {
    tasks.value = []
    currentTask.value = null
    comments.value = []
  }

  return {
    tasks,
    myTasks,
    currentTask,
    comments,
    loading,
    error,
    meta,
    tasksByStatus,
    fetchTasks,
    fetchMyTasks,
    fetchTask,
    createTask,
    updateTask,
    deleteTask,
    fetchComments,
    addComment,
    deleteComment,
    addDependency,
    removeDependency,
    setCurrentTask,
    clearTasks
  }
})
