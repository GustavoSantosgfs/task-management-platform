<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useTasksStore } from '@/stores/tasks'
import type { TaskFilters, TaskStatus, TaskPriority, Task } from '@/types'

const router = useRouter()
const tasksStore = useTasksStore()

const searchQuery = ref('')
const statusFilter = ref<TaskStatus | ''>('')
const priorityFilter = ref<TaskPriority | ''>('')
const sortBy = ref('due_date')
const sortDirection = ref<'asc' | 'desc'>('asc')
const searchTimeout = ref<ReturnType<typeof setTimeout> | null>(null)

const filters = computed<TaskFilters>(() => ({
  search: searchQuery.value || undefined,
  status: statusFilter.value || undefined,
  priority: priorityFilter.value || undefined,
  sort_by: sortBy.value,
  sort_direction: sortDirection.value,
  per_page: 50
}))

const statusOptions: { value: TaskStatus | ''; label: string }[] = [
  { value: '', label: 'All Statuses' },
  { value: 'backlog', label: 'Backlog' },
  { value: 'todo', label: 'To Do' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'review', label: 'In Review' },
  { value: 'done', label: 'Done' },
  { value: 'blocked', label: 'Blocked' }
]

const priorityOptions: { value: TaskPriority | ''; label: string }[] = [
  { value: '', label: 'All Priorities' },
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'urgent', label: 'Urgent' }
]

onMounted(() => {
  loadTasks()
})

watch([statusFilter, priorityFilter, sortBy, sortDirection], () => {
  loadTasks()
})

watch(searchQuery, () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  searchTimeout.value = setTimeout(() => {
    loadTasks()
  }, 300)
})

function loadTasks(): void {
  tasksStore.fetchMyTasks(filters.value)
}

function handleSort(column: string): void {
  if (sortBy.value === column) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = column
    sortDirection.value = 'asc'
  }
}

function handleTaskClick(task: Task): void {
  router.push(`/projects/${task.project_id}?task=${task.id}`)
}

function handleQuickStatusChange(task: Task, newStatus: TaskStatus): void {
  tasksStore.updateTask(task.project_id, task.id, { status: newStatus })
}

function getStatusClass(status: string): string {
  const classes: Record<string, string> = {
    backlog: 'bg-light text-dark',
    todo: 'bg-secondary',
    in_progress: 'bg-primary',
    review: 'bg-warning',
    done: 'bg-success',
    blocked: 'bg-danger'
  }
  return classes[status] || 'bg-secondary'
}

function getPriorityClass(priority: string): string {
  const classes: Record<string, string> = {
    low: 'bg-secondary',
    medium: 'bg-info',
    high: 'bg-warning',
    urgent: 'bg-danger'
  }
  return classes[priority] || 'bg-secondary'
}

function formatDueDate(dateString: string | null, isOverdue: boolean): string {
  if (!dateString) return '-'
  const date = new Date(dateString)
  const formatted = date.toLocaleDateString()
  return isOverdue ? `${formatted} (Overdue)` : formatted
}

function getSortIcon(column: string): string {
  if (sortBy.value !== column) return 'bi-arrow-down-up'
  return sortDirection.value === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down'
}
</script>

<template>
  <div class="my-tasks-view">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-1">My Tasks</h1>
        <p class="text-muted mb-0">Tasks assigned to you across all projects</p>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3 align-items-center">
          <div class="col-md-4">
            <div class="input-group">
              <span class="input-group-text">
                <i class="bi bi-search"></i>
              </span>
              <input
                v-model="searchQuery"
                type="text"
                class="form-control"
                placeholder="Search tasks..."
              >
            </div>
          </div>
          <div class="col-md-3">
            <select v-model="statusFilter" class="form-select">
              <option
                v-for="option in statusOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>
          <div class="col-md-3">
            <select v-model="priorityFilter" class="form-select">
              <option
                v-for="option in priorityOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>
          <div class="col-md-2">
            <span class="text-muted">
              {{ tasksStore.myTasks.length }} tasks
            </span>
          </div>
        </div>
      </div>
    </div>

    <div v-if="tasksStore.loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else-if="tasksStore.myTasks.length === 0" class="text-center py-5">
      <i class="bi bi-check-circle display-1 text-muted"></i>
      <h5 class="mt-3 text-muted">No tasks found</h5>
      <p class="text-muted">
        <template v-if="searchQuery || statusFilter || priorityFilter">
          Try adjusting your filters
        </template>
        <template v-else>
          You don't have any tasks assigned yet
        </template>
      </p>
    </div>

    <div v-else class="card">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th class="sortable" @click="handleSort('title')">
                Task
                <i :class="['bi ms-1', getSortIcon('title')]"></i>
              </th>
              <th>Project</th>
              <th class="sortable" @click="handleSort('status')">
                Status
                <i :class="['bi ms-1', getSortIcon('status')]"></i>
              </th>
              <th class="sortable" @click="handleSort('priority')">
                Priority
                <i :class="['bi ms-1', getSortIcon('priority')]"></i>
              </th>
              <th class="sortable" @click="handleSort('due_date')">
                Due Date
                <i :class="['bi ms-1', getSortIcon('due_date')]"></i>
              </th>
              <th>Quick Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="task in tasksStore.myTasks"
              :key="task.id"
              class="cursor-pointer"
              @click="handleTaskClick(task)"
            >
              <td>
                <div class="fw-medium">{{ task.title }}</div>
                <small
                  v-if="task.description"
                  class="text-muted description-truncate"
                >
                  {{ task.description }}
                </small>
              </td>
              <td>
                <router-link
                  :to="`/projects/${task.project_id}`"
                  class="text-decoration-none"
                  @click.stop
                >
                  {{ task.project?.title || `Project #${task.project_id}` }}
                </router-link>
              </td>
              <td>
                <span class="badge" :class="getStatusClass(task.status)">
                  {{ task.status.replace('_', ' ') }}
                </span>
              </td>
              <td>
                <span class="badge" :class="getPriorityClass(task.priority)">
                  {{ task.priority }}
                </span>
              </td>
              <td>
                <span :class="{ 'text-danger': task.is_overdue }">
                  {{ formatDueDate(task.due_date, task.is_overdue) }}
                </span>
              </td>
              <td @click.stop>
                <div class="dropdown">
                  <button
                    class="btn btn-sm btn-outline-secondary dropdown-toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                  >
                    Move to
                  </button>
                  <ul class="dropdown-menu">
                    <li
                      v-for="option in statusOptions.filter(o => o.value && o.value !== task.status)"
                      :key="option.value"
                    >
                      <button
                        class="dropdown-item"
                        @click="handleQuickStatusChange(task, option.value as TaskStatus)"
                      >
                        {{ option.label }}
                      </button>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div
      v-if="tasksStore.meta.last_page > 1"
      class="d-flex justify-content-center mt-4"
    >
      <nav>
        <ul class="pagination mb-0">
          <li
            class="page-item"
            :class="{ disabled: tasksStore.meta.current_page === 1 }"
          >
            <button
              class="page-link"
              @click="tasksStore.fetchMyTasks({ ...filters, page: tasksStore.meta.current_page - 1 })"
            >
              Previous
            </button>
          </li>
          <li class="page-item disabled">
            <span class="page-link">
              Page {{ tasksStore.meta.current_page }} of {{ tasksStore.meta.last_page }}
            </span>
          </li>
          <li
            class="page-item"
            :class="{ disabled: tasksStore.meta.current_page === tasksStore.meta.last_page }"
          >
            <button
              class="page-link"
              @click="tasksStore.fetchMyTasks({ ...filters, page: tasksStore.meta.current_page + 1 })"
            >
              Next
            </button>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>

<style scoped>
.sortable {
  cursor: pointer;
  user-select: none;
}

.sortable:hover {
  background-color: #f8f9fa;
}

.cursor-pointer {
  cursor: pointer;
}

.cursor-pointer:hover {
  background-color: #f8f9fa;
}

.description-truncate {
  display: block;
  max-width: 300px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
