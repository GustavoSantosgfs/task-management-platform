<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProjectsStore } from '@/stores/projects'
import { useTasksStore } from '@/stores/tasks'
import { useAuthStore } from '@/stores/auth'
import type { Task, TaskStatus } from '@/types'
import KanbanBoard from '@/components/common/KanbanBoard.vue'
import TaskModal from '@/components/common/TaskModal.vue'
import CreateTaskModal from '@/components/common/CreateTaskModal.vue'
import ProjectMembersModal from '@/components/common/ProjectMembersModal.vue'

const route = useRoute()
const router = useRouter()
const projectsStore = useProjectsStore()
const tasksStore = useTasksStore()
const authStore = useAuthStore()

const projectId = computed(() => Number(route.params.id))
const showTaskModal = ref(false)
const showCreateTaskModal = ref(false)
const showMembersModal = ref(false)
const selectedTaskId = ref<number | null>(null)
const createTaskStatus = ref<TaskStatus>('todo')

const canManageProject = computed(() =>
  authStore.canManageProject(projectsStore.currentProject?.manager?.id)
)

const statusClass = computed(() => {
  const status = projectsStore.currentProject?.status
  const classes: Record<string, string> = {
    planning: 'bg-secondary',
    active: 'bg-primary',
    on_hold: 'bg-warning',
    completed: 'bg-success',
    cancelled: 'bg-danger'
  }
  return classes[status || ''] || 'bg-secondary'
})

onMounted(() => {
  loadProjectData()

  const taskParam = route.query.task
  if (taskParam) {
    selectedTaskId.value = Number(taskParam)
    showTaskModal.value = true
  }
})

watch(() => route.params.id, () => {
  loadProjectData()
})

function loadProjectData(): void {
  projectsStore.fetchProject(projectId.value)
    .catch(() => {
      router.push('/projects')
    })
  tasksStore.fetchTasks(projectId.value, { per_page: 100 })
}

function handleTaskClick(task: Task): void {
  selectedTaskId.value = task.id
  showTaskModal.value = true
  router.replace({ query: { ...route.query, task: task.id } })
}

function handleCloseTaskModal(): void {
  showTaskModal.value = false
  selectedTaskId.value = null
  router.replace({ query: {} })
}

function handleTaskUpdated(): void {
  tasksStore.fetchTasks(projectId.value, { per_page: 100 })
}

function handleCreateTask(status: TaskStatus): void {
  createTaskStatus.value = status
  showCreateTaskModal.value = true
}

function handleTaskCreated(): void {
  showCreateTaskModal.value = false
  tasksStore.fetchTasks(projectId.value, { per_page: 100 })
}

function handleStatusChange(taskId: number, newStatus: TaskStatus): void {
  tasksStore.updateTask(projectId.value, taskId, { status: newStatus })
}

function formatDate(dateString: string | null): string {
  if (!dateString) return 'Not set'
  return new Date(dateString).toLocaleDateString()
}
</script>

<template>
  <div class="project-detail-view">
    <div v-if="projectsStore.loading && !projectsStore.currentProject" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <template v-else-if="projectsStore.currentProject">
      <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
              <li class="breadcrumb-item">
                <router-link to="/projects">Projects</router-link>
              </li>
              <li class="breadcrumb-item active">
                {{ projectsStore.currentProject.title }}
              </li>
            </ol>
          </nav>
          <h1 class="h3 mb-1">{{ projectsStore.currentProject.title }}</h1>
          <div class="d-flex align-items-center gap-3 text-muted">
            <span class="badge" :class="statusClass">
              {{ projectsStore.currentProject.status.replace('_', ' ') }}
            </span>
            <span v-if="projectsStore.currentProject.manager">
              <i class="bi bi-person me-1"></i>
              {{ projectsStore.currentProject.manager.name }}
            </span>
            <span v-if="projectsStore.currentProject.end_date">
              <i class="bi bi-calendar me-1"></i>
              Due {{ formatDate(projectsStore.currentProject.end_date) }}
            </span>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button
            class="btn btn-outline-secondary"
            @click="showMembersModal = true"
          >
            <i class="bi bi-people me-1"></i>
            Team ({{ projectsStore.currentProject.members.length }})
          </button>
          <button
            v-if="canManageProject"
            class="btn btn-primary"
            @click="handleCreateTask('todo')"
          >
            <i class="bi bi-plus-lg me-1"></i>
            Add Task
          </button>
        </div>
      </div>

      <div v-if="projectsStore.currentProject.description" class="card mb-4">
        <div class="card-body">
          <p class="mb-0 text-muted">{{ projectsStore.currentProject.description }}</p>
        </div>
      </div>

      <div class="row mb-4">
        <div class="col-md-3">
          <div class="card bg-light">
            <div class="card-body text-center">
              <div class="h4 mb-0">{{ projectsStore.currentProject.tasks_count }}</div>
              <small class="text-muted">Total Tasks</small>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card bg-light">
            <div class="card-body text-center">
              <div class="h4 mb-0">{{ projectsStore.currentProject.completed_tasks_count }}</div>
              <small class="text-muted">Completed</small>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card bg-light">
            <div class="card-body text-center">
              <div class="h4 mb-0">{{ projectsStore.currentProject.progress_percentage }}%</div>
              <small class="text-muted">Progress</small>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card bg-light">
            <div class="card-body text-center">
              <div class="h4 mb-0">{{ projectsStore.currentProject.members.length }}</div>
              <small class="text-muted">Team Members</small>
            </div>
          </div>
        </div>
      </div>

      <KanbanBoard
        :tasks="tasksStore.tasks"
        :loading="tasksStore.loading"
        @task-click="handleTaskClick"
        @create-task="handleCreateTask"
        @status-change="handleStatusChange"
      />
    </template>

    <TaskModal
      v-if="showTaskModal && selectedTaskId"
      :project-id="projectId"
      :task-id="selectedTaskId"
      @close="handleCloseTaskModal"
      @updated="handleTaskUpdated"
    />

    <CreateTaskModal
      v-if="showCreateTaskModal"
      :project-id="projectId"
      :initial-status="createTaskStatus"
      :members="projectsStore.currentProject?.members || []"
      @close="showCreateTaskModal = false"
      @created="handleTaskCreated"
    />

    <ProjectMembersModal
      v-if="showMembersModal && projectsStore.currentProject"
      :project="projectsStore.currentProject"
      :can-manage="canManageProject"
      @close="showMembersModal = false"
    />
  </div>
</template>

<style scoped>
.project-detail-view {
  min-height: calc(100vh - 120px);
}
</style>
