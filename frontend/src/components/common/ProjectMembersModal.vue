<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useProjectsStore } from '@/stores/projects'
import Swal from 'sweetalert2'
import type { Project, User } from '@/types'

const props = defineProps<{
  project: Project
  canManage: boolean
}>()

const emit = defineEmits<{
  close: []
}>()

const projectsStore = useProjectsStore()

const loading = ref(false)

onMounted(() => {
  document.body.classList.add('modal-open')
})

function handleClose(): void {
  document.body.classList.remove('modal-open')
  emit('close')
}

function removeMember(member: User): void {
  Swal.fire({
    title: 'Remove Member?',
    text: `Remove ${member.name} from this project?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, remove'
  }).then((result) => {
    if (result.isConfirmed) {
      loading.value = true
      projectsStore.removeMember(props.project.id, member.id)
        .then(() => {
          Swal.fire({
            title: 'Removed!',
            text: `${member.name} has been removed from the project.`,
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
          })
        })
        .finally(() => {
          loading.value = false
        })
    }
  })
}

function getRoleBadgeClass(role: string): string {
  const classes: Record<string, string> = {
    admin: 'bg-danger',
    project_manager: 'bg-primary',
    member: 'bg-secondary'
  }
  return classes[role] || 'bg-secondary'
}

function formatRole(role: string): string {
  const labels: Record<string, string> = {
    admin: 'Admin',
    project_manager: 'PM',
    member: 'Member'
  }
  return labels[role] || role
}
</script>

<template>
  <div class="modal-backdrop fade show"></div>
  <div class="modal fade show d-block" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Team Members</h5>
          <button
            type="button"
            class="btn-close"
            @click="handleClose"
          ></button>
        </div>

        <div class="modal-body">
          <div v-if="project.manager" class="mb-4">
            <h6 class="text-muted small mb-2">Project Manager</h6>
            <div class="d-flex align-items-center p-2 bg-light rounded">
              <div class="avatar-circle me-3">
                {{ project.manager.name.charAt(0).toUpperCase() }}
              </div>
              <div class="flex-grow-1">
                <div class="fw-medium">{{ project.manager.name }}</div>
                <div class="text-muted small">{{ project.manager.email }}</div>
              </div>
              <span class="badge bg-primary">Manager</span>
            </div>
          </div>

          <div>
            <h6 class="text-muted small mb-2">
              Team Members ({{ project.members.length }})
            </h6>

            <div v-if="project.members.length === 0" class="text-center text-muted py-4">
              <i class="bi bi-people display-6"></i>
              <p class="mb-0 mt-2">No team members assigned</p>
            </div>

            <div
              v-for="member in project.members"
              :key="member.id"
              class="d-flex align-items-center p-2 border-bottom"
            >
              <div class="avatar-circle me-3">
                {{ member.name.charAt(0).toUpperCase() }}
              </div>
              <div class="flex-grow-1">
                <div class="fw-medium">{{ member.name }}</div>
                <div class="text-muted small">{{ member.email }}</div>
              </div>
              <span
                class="badge me-2"
                :class="getRoleBadgeClass(member.role)"
              >
                {{ formatRole(member.role) }}
              </span>
              <button
                v-if="canManage && member.id !== project.manager?.id"
                class="btn btn-link btn-sm text-danger p-0"
                :disabled="loading"
                @click="removeMember(member)"
              >
                <i class="bi bi-x-lg"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            @click="handleClose"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal {
  background-color: rgba(0, 0, 0, 0.5);
}

.avatar-circle {
  width: 40px;
  height: 40px;
  background-color: #6c757d;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  color: white;
  font-size: 16px;
}
</style>
