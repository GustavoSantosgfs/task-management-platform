<script setup lang="ts">
import { computed } from 'vue'
import type { Task, TaskStatus } from '@/types'

const props = defineProps<{
  tasks: Task[]
  loading: boolean
}>()

const emit = defineEmits<{
  'task-click': [task: Task]
  'create-task': [status: TaskStatus]
  'status-change': [taskId: number, newStatus: TaskStatus]
}>()

interface Column {
  status: TaskStatus
  title: string
  color: string
}

const columns: Column[] = [
  { status: 'backlog', title: 'Backlog', color: '#adb5bd' },
  { status: 'todo', title: 'To Do', color: '#6c757d' },
  { status: 'in_progress', title: 'In Progress', color: '#0d6efd' },
  { status: 'review', title: 'In Review', color: '#ffc107' },
  { status: 'done', title: 'Done', color: '#198754' },
  { status: 'blocked', title: 'Blocked', color: '#dc3545' }
]

const tasksByStatus = computed(() => {
  const grouped: Record<TaskStatus, Task[]> = {
    backlog: [],
    todo: [],
    in_progress: [],
    review: [],
    done: [],
    blocked: []
  }
  props.tasks.forEach(task => {
    if (grouped[task.status]) {
      grouped[task.status].push(task)
    }
  })
  return grouped
})

function handleDragStart(event: DragEvent, task: Task): void {
  if (event.dataTransfer) {
    event.dataTransfer.setData('taskId', String(task.id))
    event.dataTransfer.effectAllowed = 'move'
  }
}

function handleDragOver(event: DragEvent): void {
  event.preventDefault()
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'move'
  }
}

function handleDrop(event: DragEvent, status: TaskStatus): void {
  event.preventDefault()
  const taskId = event.dataTransfer?.getData('taskId')
  if (taskId) {
    emit('status-change', Number(taskId), status)
  }
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
  if (!dateString) return ''
  const date = new Date(dateString)
  const today = new Date()
  const diffDays = Math.ceil((date.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))

  if (isOverdue) return 'Overdue'
  if (diffDays === 0) return 'Today'
  if (diffDays === 1) return 'Tomorrow'
  if (diffDays < 7) return `${diffDays} days`
  return date.toLocaleDateString()
}
</script>

<template>
  <div class="kanban-board">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else class="kanban-columns">
      <div
        v-for="column in columns"
        :key="column.status"
        class="kanban-column"
        @dragover="handleDragOver"
        @drop="handleDrop($event, column.status)"
      >
        <div class="column-header">
          <div class="d-flex align-items-center">
            <span
              class="column-indicator"
              :style="{ backgroundColor: column.color }"
            ></span>
            <span class="fw-medium">{{ column.title }}</span>
            <span class="badge bg-light text-dark ms-2">
              {{ tasksByStatus[column.status].length }}
            </span>
          </div>
          <button
            class="btn btn-link btn-sm p-0 text-muted"
            @click="emit('create-task', column.status)"
          >
            <i class="bi bi-plus-lg"></i>
          </button>
        </div>

        <div class="column-content">
          <div
            v-for="task in tasksByStatus[column.status]"
            :key="task.id"
            class="task-card"
            draggable="true"
            @dragstart="handleDragStart($event, task)"
            @click="emit('task-click', task)"
          >
            <div class="d-flex justify-content-between align-items-start mb-2">
              <span class="badge" :class="getPriorityClass(task.priority)">
                {{ task.priority }}
              </span>
              <span
                v-if="task.due_date"
                class="small"
                :class="{ 'text-danger': task.is_overdue, 'text-muted': !task.is_overdue }"
              >
                <i class="bi bi-clock me-1"></i>
                {{ formatDueDate(task.due_date, task.is_overdue) }}
              </span>
            </div>

            <h6 class="task-title mb-2">{{ task.title }}</h6>

            <p v-if="task.description" class="task-description text-muted small mb-2">
              {{ task.description }}
            </p>

            <div class="d-flex justify-content-between align-items-center">
              <div v-if="task.assignee" class="d-flex align-items-center">
                <div class="avatar-xs me-1">
                  {{ task.assignee.name.charAt(0).toUpperCase() }}
                </div>
                <small class="text-muted">{{ task.assignee.name }}</small>
              </div>
              <div v-else class="text-muted small">Unassigned</div>

              <div class="d-flex align-items-center gap-2 text-muted small">
                <span v-if="task.comments_count">
                  <i class="bi bi-chat me-1"></i>{{ task.comments_count }}
                </span>
                <span v-if="task.dependencies?.length">
                  <i class="bi bi-link-45deg me-1"></i>{{ task.dependencies.length }}
                </span>
              </div>
            </div>

            <div
              v-if="task.has_uncompleted_dependencies"
              class="alert alert-warning py-1 px-2 mt-2 mb-0 small"
            >
              <i class="bi bi-exclamation-triangle me-1"></i>
              Has uncompleted dependencies
            </div>
          </div>

          <div
            v-if="tasksByStatus[column.status].length === 0"
            class="empty-column text-center text-muted py-4"
          >
            <i class="bi bi-inbox"></i>
            <p class="small mb-0 mt-2">No tasks</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.kanban-board {
  overflow-x: auto;
  padding-bottom: 1rem;
}

.kanban-columns {
  display: flex;
  gap: 1rem;
  min-width: max-content;
}

.kanban-column {
  width: 300px;
  min-width: 300px;
  background-color: #f8f9fa;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
}

.column-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #e9ecef;
}

.column-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  margin-right: 8px;
}

.column-content {
  flex: 1;
  padding: 12px;
  overflow-y: auto;
  max-height: calc(100vh - 380px);
}

.task-card {
  background: white;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 8px;
  cursor: pointer;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
}

.task-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.task-card:active {
  cursor: grabbing;
}

.task-title {
  font-size: 14px;
  font-weight: 500;
  margin: 0;
}

.task-description {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  font-size: 13px;
  line-height: 1.4;
}

.avatar-xs {
  width: 24px;
  height: 24px;
  background-color: #6c757d;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 500;
  font-size: 11px;
}

.empty-column {
  color: #adb5bd;
}

.empty-column i {
  font-size: 24px;
}
</style>
