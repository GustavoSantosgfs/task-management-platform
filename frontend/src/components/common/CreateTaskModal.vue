<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useTasksStore } from '@/stores/tasks'
import type { CreateTaskData, TaskStatus, TaskPriority, User } from '@/types'

const props = defineProps<{
  projectId: number
  initialStatus: TaskStatus
  members: User[]
}>()

const emit = defineEmits<{
  close: []
  created: []
}>()

const tasksStore = useTasksStore()

const form = ref<CreateTaskData>({
  title: '',
  description: '',
  status: props.initialStatus,
  priority: 'medium',
  assignee_id: undefined,
  due_date: ''
})

const loading = ref(false)
const errors = ref<Record<string, string[]>>({})

const statusOptions: { value: TaskStatus; label: string }[] = [
  { value: 'backlog', label: 'Backlog' },
  { value: 'todo', label: 'To Do' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'review', label: 'In Review' },
  { value: 'done', label: 'Done' },
  { value: 'blocked', label: 'Blocked' }
]

const priorityOptions: { value: TaskPriority; label: string }[] = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'urgent', label: 'Urgent' }
]

onMounted(() => {
  document.body.classList.add('modal-open')
})

function handleClose(): void {
  document.body.classList.remove('modal-open')
  emit('close')
}

function handleSubmit(): void {
  errors.value = {}

  if (!form.value.title.trim()) {
    errors.value.title = ['Title is required']
    return
  }

  loading.value = true

  const data: CreateTaskData = {
    title: form.value.title.trim(),
    description: form.value.description?.trim() || undefined,
    status: form.value.status,
    priority: form.value.priority,
    assignee_id: form.value.assignee_id || undefined,
    due_date: form.value.due_date || undefined
  }

  tasksStore.createTask(props.projectId, data)
    .then(() => {
      document.body.classList.remove('modal-open')
      emit('created')
    })
    .catch((err: unknown) => {
      const axiosError = err as { response?: { data?: { error?: { details?: Record<string, string[]> } } } }
      if (axiosError.response?.data?.error?.details) {
        errors.value = axiosError.response.data.error.details
      }
    })
    .finally(() => {
      loading.value = false
    })
}
</script>

<template>
  <div class="modal-backdrop fade show"></div>
  <div class="modal fade show d-block" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create New Task</h5>
          <button
            type="button"
            class="btn-close"
            @click="handleClose"
          ></button>
        </div>

        <form @submit.prevent="handleSubmit">
          <div class="modal-body">
            <div class="mb-3">
              <label for="title" class="form-label">
                Title <span class="text-danger">*</span>
              </label>
              <input
                id="title"
                v-model="form.title"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.title }"
                placeholder="Enter task title"
              >
              <div v-if="errors.title" class="invalid-feedback">
                {{ errors.title[0] }}
              </div>
            </div>

            <div class="mb-3">
              <label for="description" class="form-label">Description</label>
              <textarea
                id="description"
                v-model="form.description"
                class="form-control"
                rows="3"
                placeholder="Enter task description"
              ></textarea>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="status" class="form-label">Status</label>
                <select id="status" v-model="form.status" class="form-select">
                  <option
                    v-for="option in statusOptions"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
              </div>

              <div class="col-md-6 mb-3">
                <label for="priority" class="form-label">Priority</label>
                <select id="priority" v-model="form.priority" class="form-select">
                  <option
                    v-for="option in priorityOptions"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="assignee" class="form-label">Assignee</label>
                <select id="assignee" v-model="form.assignee_id" class="form-select">
                  <option :value="undefined">Unassigned</option>
                  <option
                    v-for="member in members"
                    :key="member.id"
                    :value="member.id"
                  >
                    {{ member.name }}
                  </option>
                </select>
              </div>

              <div class="col-md-6 mb-3">
                <label for="due_date" class="form-label">Due Date</label>
                <input
                  id="due_date"
                  v-model="form.due_date"
                  type="date"
                  class="form-control"
                >
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary"
              @click="handleClose"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="loading"
            >
              <span v-if="loading">
                <span class="spinner-border spinner-border-sm me-1"></span>
                Creating...
              </span>
              <span v-else>Create Task</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal {
  background-color: rgba(0, 0, 0, 0.5);
}
</style>
