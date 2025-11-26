<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useProjectsStore } from '@/stores/projects'
import type { CreateProjectData, ProjectStatus } from '@/types'

const emit = defineEmits<{
  close: []
  created: []
}>()

const projectsStore = useProjectsStore()

const form = ref<CreateProjectData>({
  title: '',
  description: '',
  status: 'planning',
  visibility: 'public',
  start_date: '',
  end_date: ''
})

const loading = ref(false)
const errors = ref<Record<string, string[]>>({})

const statusOptions: { value: ProjectStatus; label: string }[] = [
  { value: 'planning', label: 'Planning' },
  { value: 'active', label: 'Active' },
  { value: 'on_hold', label: 'On Hold' }
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

  const data: CreateProjectData = {
    title: form.value.title.trim(),
    description: form.value.description?.trim() || undefined,
    status: form.value.status,
    visibility: form.value.visibility,
    start_date: form.value.start_date || undefined,
    end_date: form.value.end_date || undefined
  }

  projectsStore.createProject(data)
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
          <h5 class="modal-title">Create New Project</h5>
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
                placeholder="Enter project title"
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
                placeholder="Enter project description"
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
                <label for="visibility" class="form-label">Visibility</label>
                <select id="visibility" v-model="form.visibility" class="form-select">
                  <option value="public">Public</option>
                  <option value="private">Private</option>
                </select>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input
                  id="start_date"
                  v-model="form.start_date"
                  type="date"
                  class="form-control"
                >
              </div>

              <div class="col-md-6 mb-3">
                <label for="end_date" class="form-label">End Date</label>
                <input
                  id="end_date"
                  v-model="form.end_date"
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
              <span v-else>Create Project</span>
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
