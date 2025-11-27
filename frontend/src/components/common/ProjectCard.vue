<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useProjectsStore } from '@/stores/projects'
import { useAuthStore } from '@/stores/auth'
import Swal from 'sweetalert2'
import type { Project } from '@/types'

const props = defineProps<{
  project: Project
}>()

const emit = defineEmits<{
  deleted: []
}>()

const router = useRouter()
const projectsStore = useProjectsStore()
const authStore = useAuthStore()

const canManage = computed(() =>
  authStore.canManageProject(props.project.manager?.id)
)

const statusClass = computed(() => {
  const classes: Record<string, string> = {
    planning: 'bg-secondary',
    active: 'bg-primary',
    on_hold: 'bg-warning',
    completed: 'bg-success',
    cancelled: 'bg-danger'
  }
  return classes[props.project.status] || 'bg-secondary'
})

const isArchived = computed(() => props.project.deleted_at !== null)

function navigateToProject(): void {
  router.push(`/projects/${props.project.id}`)
}

function handleDelete(): void {
  Swal.fire({
    title: 'Archive Project?',
    text: `Are you sure you want to archive "${props.project.title}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, archive it'
  }).then((result) => {
    if (result.isConfirmed) {
      projectsStore.deleteProject(props.project.id)
        .then(() => {
          emit('deleted')
          Swal.fire({
            title: 'Archived!',
            text: 'Project has been archived.',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
          })
        })
        .catch(() => {
          Swal.fire({
            title: 'Error!',
            text: 'Failed to archive project.',
            icon: 'error'
          })
        })
    }
  })
}

function handleRestore(): void {
  Swal.fire({
    title: 'Restore Project?',
    text: `Are you sure you want to restore "${props.project.title}"?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#198754',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, restore it'
  }).then((result) => {
    if (result.isConfirmed) {
      projectsStore.restoreProject(props.project.id)
        .then(() => {
          emit('deleted')
          Swal.fire({
            title: 'Restored!',
            text: 'Project has been restored.',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
          })
        })
        .catch(() => {
          Swal.fire({
            title: 'Error!',
            text: 'Failed to restore project.',
            icon: 'error'
          })
        })
    }
  })
}

function formatDate(dateString: string | null): string {
  if (!dateString) return 'No date'
  return new Date(dateString).toLocaleDateString()
}
</script>

<template>
  <div
    class="card h-100 project-card"
    :class="{ 'border-secondary opacity-75': isArchived }"
    @click="navigateToProject"
  >
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <span class="badge" :class="statusClass">
          {{ project.status.replace('_', ' ') }}
        </span>
        <div v-if="canManage" class="dropdown" @click.stop>
          <button
            class="btn btn-link btn-sm text-muted p-0"
            type="button"
            data-bs-toggle="dropdown"
          >
            <i class="bi bi-three-dots-vertical"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li v-if="!isArchived">
              <button class="dropdown-item text-danger" @click="handleDelete">
                <i class="bi bi-archive me-2"></i>Archive
              </button>
            </li>
            <li v-else>
              <button class="dropdown-item" @click="handleRestore">
                <i class="bi bi-arrow-counterclockwise me-2"></i>Restore
              </button>
            </li>
          </ul>
        </div>
      </div>

      <h5 class="card-title mb-2">{{ project.title }}</h5>

      <p v-if="project.description" class="card-text text-muted small mb-3 description-truncate">
        {{ project.description }}
      </p>

      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <small class="text-muted">Progress</small>
          <small class="text-muted">{{ project.progress_percentage }}%</small>
        </div>
        <div class="progress" style="height: 6px;">
          <div
            class="progress-bar"
            :class="{
              'bg-success': project.progress_percentage === 100,
              'bg-primary': project.progress_percentage < 100
            }"
            :style="{ width: project.progress_percentage + '%' }"
          ></div>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center text-muted small">
        <div>
          <i class="bi bi-list-task me-1"></i>
          {{ project.completed_tasks_count }}/{{ project.tasks_count }} tasks
        </div>
        <div v-if="project.end_date">
          <i class="bi bi-calendar me-1"></i>
          {{ formatDate(project.end_date) }}
        </div>
      </div>
    </div>

    <div class="card-footer bg-transparent border-top-0">
      <div class="d-flex align-items-center">
        <div v-if="project.manager" class="d-flex align-items-center">
          <div class="avatar-sm me-2">
            {{ project.manager.name.charAt(0).toUpperCase() }}
          </div>
          <small class="text-muted">{{ project.manager.name }}</small>
        </div>
        <div v-else class="text-muted small">
          No manager assigned
        </div>

        <div class="ms-auto">
          <div class="avatar-group">
            <div
              v-for="(member, index) in project.members.slice(0, 3)"
              :key="member.id"
              class="avatar-sm"
              :title="member.name"
              :style="{ marginLeft: index > 0 ? '-8px' : '0', zIndex: 3 - index }"
            >
              {{ member.name.charAt(0).toUpperCase() }}
            </div>
            <div
              v-if="project.members.length > 3"
              class="avatar-sm bg-secondary"
              style="margin-left: -8px;"
            >
              +{{ project.members.length - 3 }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.project-card {
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.project-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.description-truncate {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.avatar-sm {
  width: 28px;
  height: 28px;
  background-color: #6c757d;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 500;
  font-size: 12px;
  border: 2px solid white;
}

.avatar-group {
  display: flex;
}
</style>
