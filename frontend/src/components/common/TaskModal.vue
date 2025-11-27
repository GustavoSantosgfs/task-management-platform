<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useTasksStore } from '@/stores/tasks'
import { useProjectsStore } from '@/stores/projects'
import { useAuthStore } from '@/stores/auth'
import Swal from 'sweetalert2'
import type { TaskStatus, TaskPriority, UpdateTaskData } from '@/types'

const props = defineProps<{
  projectId: number
  taskId: number
}>()

const emit = defineEmits<{
  close: []
  updated: []
}>()

const tasksStore = useTasksStore()
const projectsStore = useProjectsStore()
const authStore = useAuthStore()

const isEditing = ref(false)
const newComment = ref('')
const submittingComment = ref(false)
const loading = ref(false)

const editForm = ref<UpdateTaskData>({})

const task = computed(() => tasksStore.currentTask)

const canEdit = computed(() => {
  if (!task.value) return false
  return authStore.canUpdateTask(task.value.assignee?.id)
})

const statusOptions: { value: TaskStatus; label: string; color: string }[] = [
  { value: 'todo', label: 'To Do', color: '#6c757d' },
  { value: 'in_progress', label: 'In Progress', color: '#0d6efd' },
  { value: 'review', label: 'In Review', color: '#ffc107' },
  { value: 'done', label: 'Done', color: '#198754' },
  { value: 'blocked', label: 'Blocked', color: '#dc3545' }
]

const priorityOptions: { value: TaskPriority; label: string }[] = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'urgent', label: 'Urgent' }
]

onMounted(() => {
  document.body.classList.add('modal-open')
  loadTask()
})

watch(() => props.taskId, () => {
  loadTask()
})

function loadTask(): void {
  loading.value = true
  tasksStore.fetchTask(props.projectId, props.taskId)
    .then(() => {
      tasksStore.fetchComments(props.projectId, props.taskId)
    })
    .finally(() => {
      loading.value = false
    })
}

function handleClose(): void {
  document.body.classList.remove('modal-open')
  tasksStore.setCurrentTask(null)
  emit('close')
}

function startEditing(): void {
  if (task.value) {
    editForm.value = {
      title: task.value.title,
      description: task.value.description || '',
      status: task.value.status,
      priority: task.value.priority,
      assignee_id: task.value.assignee?.id,
      due_date: task.value.due_date?.split('T')[0] || ''
    }
    isEditing.value = true
  }
}

function cancelEditing(): void {
  isEditing.value = false
  editForm.value = {}
}

function saveChanges(): void {
  loading.value = true
  tasksStore.updateTask(props.projectId, props.taskId, editForm.value)
    .then(() => {
      isEditing.value = false
      emit('updated')
    })
    .finally(() => {
      loading.value = false
    })
}

function handleStatusChange(status: TaskStatus): void {
  tasksStore.updateTask(props.projectId, props.taskId, { status })
    .then(() => {
      emit('updated')
    })
}

function submitComment(): void {
  if (!newComment.value.trim()) return

  submittingComment.value = true
  tasksStore.addComment(props.projectId, props.taskId, newComment.value.trim())
    .then(() => {
      newComment.value = ''
    })
    .finally(() => {
      submittingComment.value = false
    })
}

function deleteComment(commentId: number): void {
  Swal.fire({
    title: 'Delete Comment?',
    text: 'Are you sure you want to delete this comment?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, delete it'
  }).then((result) => {
    if (result.isConfirmed) {
      tasksStore.deleteComment(props.projectId, props.taskId, commentId)
    }
  })
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleString()
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
</script>

<template>
  <div class="modal-backdrop fade show"></div>
  <div class="modal fade show d-block" tabindex="-1" @click.self="handleClose">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 v-if="!isEditing" class="modal-title">
            {{ task?.title || 'Loading...' }}
          </h5>
          <input
            v-else
            v-model="editForm.title"
            type="text"
            class="form-control form-control-lg"
            placeholder="Task title"
          >
          <button
            type="button"
            class="btn-close"
            @click="handleClose"
          ></button>
        </div>

        <div class="modal-body">
          <div v-if="loading && !task" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>

          <template v-else-if="task">
            <div class="row">
              <div class="col-md-8">
                <div class="mb-4">
                  <h6 class="text-muted mb-2">Description</h6>
                  <textarea
                    v-if="isEditing"
                    v-model="editForm.description"
                    class="form-control"
                    rows="4"
                    placeholder="Add a description..."
                  ></textarea>
                  <p v-else-if="task.description" class="mb-0">
                    {{ task.description }}
                  </p>
                  <p v-else class="text-muted mb-0">No description</p>
                </div>

                <div v-if="task.dependencies && task.dependencies.length > 0" class="mb-4">
                  <h6 class="text-muted mb-2">Dependencies</h6>
                  <div class="list-group">
                    <div
                      v-for="dep in task.dependencies"
                      :key="dep.id"
                      class="list-group-item d-flex justify-content-between align-items-center"
                    >
                      <span>{{ dep.title }}</span>
                      <span
                        class="badge"
                        :class="{
                          'bg-success': dep.is_done,
                          'bg-secondary': !dep.is_done
                        }"
                      >
                        {{ dep.status }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="mb-4">
                  <h6 class="text-muted mb-3">Comments</h6>

                  <div class="mb-3">
                    <div class="d-flex gap-2">
                      <div class="avatar-sm">
                        {{ authStore.user?.name?.charAt(0).toUpperCase() || 'U' }}
                      </div>
                      <div class="flex-grow-1">
                        <textarea
                          v-model="newComment"
                          class="form-control"
                          rows="2"
                          placeholder="Write a comment..."
                          @keydown.ctrl.enter="submitComment"
                        ></textarea>
                        <div class="text-end mt-2">
                          <button
                            class="btn btn-primary btn-sm"
                            :disabled="!newComment.trim() || submittingComment"
                            @click="submitComment"
                          >
                            <span v-if="submittingComment">
                              <span class="spinner-border spinner-border-sm me-1"></span>
                              Posting...
                            </span>
                            <span v-else>Post Comment</span>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-if="tasksStore.comments.length === 0" class="text-center text-muted py-3">
                    No comments yet
                  </div>

                  <div
                    v-for="comment in tasksStore.comments"
                    :key="comment.id"
                    class="d-flex gap-2 mb-3"
                  >
                    <div class="avatar-sm">
                      {{ comment.user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="flex-grow-1">
                      <div class="bg-light rounded p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                          <div>
                            <strong>{{ comment.user.name }}</strong>
                            <small class="text-muted ms-2">
                              {{ formatDate(comment.created_at) }}
                            </small>
                          </div>
                          <button
                            v-if="comment.user.id === authStore.user?.id"
                            class="btn btn-link btn-sm text-danger p-0"
                            @click="deleteComment(comment.id)"
                          >
                            <i class="bi bi-trash"></i>
                          </button>
                        </div>
                        <p class="mb-0">{{ comment.content }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="card bg-light">
                  <div class="card-body">
                    <div class="mb-3">
                      <label class="form-label text-muted small">Status</label>
                      <select
                        v-if="isEditing"
                        v-model="editForm.status"
                        class="form-select"
                      >
                        <option
                          v-for="option in statusOptions"
                          :key="option.value"
                          :value="option.value"
                        >
                          {{ option.label }}
                        </option>
                      </select>
                      <div v-else class="dropdown">
                        <button
                          class="btn btn-outline-secondary w-100 text-start d-flex justify-content-between align-items-center"
                          :disabled="!canEdit"
                          type="button"
                          data-bs-toggle="dropdown"
                        >
                          <span>{{ task.status.replace('_', ' ') }}</span>
                          <i class="bi bi-chevron-down"></i>
                        </button>
                        <ul class="dropdown-menu w-100">
                          <li v-for="option in statusOptions" :key="option.value">
                            <button
                              class="dropdown-item"
                              @click="handleStatusChange(option.value)"
                            >
                              <span
                                class="status-dot me-2"
                                :style="{ backgroundColor: option.color }"
                              ></span>
                              {{ option.label }}
                            </button>
                          </li>
                        </ul>
                      </div>
                    </div>

                    <div class="mb-3">
                      <label class="form-label text-muted small">Priority</label>
                      <select
                        v-if="isEditing"
                        v-model="editForm.priority"
                        class="form-select"
                      >
                        <option
                          v-for="option in priorityOptions"
                          :key="option.value"
                          :value="option.value"
                        >
                          {{ option.label }}
                        </option>
                      </select>
                      <div v-else>
                        <span class="badge" :class="getPriorityClass(task.priority)">
                          {{ task.priority }}
                        </span>
                      </div>
                    </div>

                    <div class="mb-3">
                      <label class="form-label text-muted small">Assignee</label>
                      <select
                        v-if="isEditing"
                        v-model="editForm.assignee_id"
                        class="form-select"
                      >
                        <option :value="undefined">Unassigned</option>
                        <option
                          v-for="member in projectsStore.currentProject?.members"
                          :key="member.id"
                          :value="member.id"
                        >
                          {{ member.name }}
                        </option>
                      </select>
                      <div v-else-if="task.assignee" class="d-flex align-items-center">
                        <div class="avatar-sm me-2">
                          {{ task.assignee.name.charAt(0).toUpperCase() }}
                        </div>
                        <span>{{ task.assignee.name }}</span>
                      </div>
                      <span v-else class="text-muted">Unassigned</span>
                    </div>

                    <div class="mb-3">
                      <label class="form-label text-muted small">Due Date</label>
                      <input
                        v-if="isEditing"
                        v-model="editForm.due_date"
                        type="date"
                        class="form-control"
                      >
                      <div v-else-if="task.due_date">
                        <span :class="{ 'text-danger': task.is_overdue }">
                          {{ new Date(task.due_date).toLocaleDateString() }}
                        </span>
                        <span v-if="task.is_overdue" class="badge bg-danger ms-2">
                          Overdue
                        </span>
                      </div>
                      <span v-else class="text-muted">No due date</span>
                    </div>

                    <hr>

                    <div class="small text-muted">
                      <div class="mb-1">
                        Created: {{ formatDate(task.created_at) }}
                      </div>
                      <div v-if="task.creator">
                        By: {{ task.creator.name }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </div>

        <div v-if="task && canEdit" class="modal-footer">
          <template v-if="isEditing">
            <button
              type="button"
              class="btn btn-secondary"
              @click="cancelEditing"
            >
              Cancel
            </button>
            <button
              type="button"
              class="btn btn-primary"
              :disabled="loading"
              @click="saveChanges"
            >
              <span v-if="loading">
                <span class="spinner-border spinner-border-sm me-1"></span>
                Saving...
              </span>
              <span v-else>Save Changes</span>
            </button>
          </template>
          <template v-else>
            <button
              type="button"
              class="btn btn-outline-primary"
              @click="startEditing"
            >
              <i class="bi bi-pencil me-1"></i>
              Edit Task
            </button>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal {
  background-color: rgba(0, 0, 0, 0.5);
}

.avatar-sm {
  width: 32px;
  height: 32px;
  min-width: 32px;
  background-color: #6c757d;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 500;
  font-size: 14px;
}

.status-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
}
</style>
