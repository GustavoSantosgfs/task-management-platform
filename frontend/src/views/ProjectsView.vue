<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { useProjectsStore } from '@/stores/projects'
import { useAuthStore } from '@/stores/auth'
import type { ProjectFilters, ProjectStatus } from '@/types'
import ProjectCard from '@/components/common/ProjectCard.vue'
import CreateProjectModal from '@/components/common/CreateProjectModal.vue'

const projectsStore = useProjectsStore()
const authStore = useAuthStore()

const showCreateModal = ref(false)
const viewMode = ref<'grid' | 'list'>('grid')
const showArchived = ref(false)
const searchQuery = ref('')
const statusFilter = ref<ProjectStatus | ''>('')
const searchTimeout = ref<ReturnType<typeof setTimeout> | null>(null)

const filters = computed<ProjectFilters>(() => ({
  search: searchQuery.value || undefined,
  status: statusFilter.value || undefined,
  include_archived: showArchived.value || undefined,
  per_page: 20
}))

const canCreateProject = computed(() =>
  authStore.isAdmin || authStore.isProjectManager
)

const statusOptions: { value: ProjectStatus | ''; label: string }[] = [
  { value: '', label: 'All Statuses' },
  { value: 'planning', label: 'Planning' },
  { value: 'active', label: 'Active' },
  { value: 'on_hold', label: 'On Hold' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' }
]

onMounted(() => {
  loadProjects()
})

watch([statusFilter, showArchived], () => {
  loadProjects()
})

watch(searchQuery, () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  searchTimeout.value = setTimeout(() => {
    loadProjects()
  }, 300)
})

function loadProjects(): void {
  projectsStore.fetchProjects(filters.value)
}

function handleProjectCreated(): void {
  showCreateModal.value = false
  loadProjects()
}

function handleProjectDeleted(): void {
  loadProjects()
}
</script>

<template>
  <div class="projects-view">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-1">Projects</h1>
        <p class="text-muted mb-0">Manage your team's projects</p>
      </div>
      <button
        v-if="canCreateProject"
        class="btn btn-primary"
        @click="showCreateModal = true"
      >
        <i class="bi bi-plus-lg me-1"></i>
        New Project
      </button>
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
                placeholder="Search projects..."
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
            <div class="form-check">
              <input
                id="showArchived"
                v-model="showArchived"
                type="checkbox"
                class="form-check-input"
              >
              <label for="showArchived" class="form-check-label">
                Show archived
              </label>
            </div>
          </div>
          <div class="col-md-2">
            <div class="btn-group w-100">
              <button
                class="btn btn-outline-secondary"
                :class="{ active: viewMode === 'grid' }"
                @click="viewMode = 'grid'"
              >
                <i class="bi bi-grid"></i>
              </button>
              <button
                class="btn btn-outline-secondary"
                :class="{ active: viewMode === 'list' }"
                @click="viewMode = 'list'"
              >
                <i class="bi bi-list"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="projectsStore.loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else-if="projectsStore.projects.length === 0" class="text-center py-5">
      <i class="bi bi-folder2-open display-1 text-muted"></i>
      <h5 class="mt-3 text-muted">No projects found</h5>
      <p class="text-muted">
        <template v-if="searchQuery || statusFilter">
          Try adjusting your filters
        </template>
        <template v-else-if="canCreateProject">
          Create your first project to get started
        </template>
        <template v-else>
          You don't have access to any projects yet
        </template>
      </p>
    </div>

    <template v-else>
      <div
        v-if="viewMode === 'grid'"
        class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4"
      >
        <div v-for="project in projectsStore.projects" :key="project.id" class="col">
          <ProjectCard
            :project="project"
            @deleted="handleProjectDeleted"
          />
        </div>
      </div>

      <div v-else class="card">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Project</th>
                <th>Status</th>
                <th>Progress</th>
                <th>Manager</th>
                <th>Due Date</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="project in projectsStore.projects"
                :key="project.id"
                class="cursor-pointer"
                @click="$router.push(`/projects/${project.id}`)"
              >
                <td>
                  <div class="fw-medium">{{ project.title }}</div>
                  <small class="text-muted">{{ project.tasks_count }} tasks</small>
                </td>
                <td>
                  <span
                    class="badge"
                    :class="{
                      'bg-secondary': project.status === 'planning',
                      'bg-primary': project.status === 'active',
                      'bg-warning': project.status === 'on_hold',
                      'bg-success': project.status === 'completed',
                      'bg-danger': project.status === 'cancelled'
                    }"
                  >
                    {{ project.status }}
                  </span>
                </td>
                <td>
                  <div class="progress" style="width: 100px; height: 8px;">
                    <div
                      class="progress-bar"
                      :style="{ width: project.progress_percentage + '%' }"
                    ></div>
                  </div>
                  <small class="text-muted">{{ project.progress_percentage }}%</small>
                </td>
                <td>
                  <span v-if="project.manager">{{ project.manager.name }}</span>
                  <span v-else class="text-muted">Unassigned</span>
                </td>
                <td>
                  <span v-if="project.end_date">
                    {{ new Date(project.end_date).toLocaleDateString() }}
                  </span>
                  <span v-else class="text-muted">No due date</span>
                </td>
                <td>
                  <router-link
                    :to="`/projects/${project.id}`"
                    class="btn btn-sm btn-outline-primary"
                    @click.stop
                  >
                    View
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div
        v-if="projectsStore.meta.last_page > 1"
        class="d-flex justify-content-center mt-4"
      >
        <nav>
          <ul class="pagination mb-0">
            <li
              class="page-item"
              :class="{ disabled: projectsStore.meta.current_page === 1 }"
            >
              <button
                class="page-link"
                @click="projectsStore.fetchProjects({ ...filters, page: projectsStore.meta.current_page - 1 })"
              >
                Previous
              </button>
            </li>
            <li class="page-item disabled">
              <span class="page-link">
                Page {{ projectsStore.meta.current_page }} of {{ projectsStore.meta.last_page }}
              </span>
            </li>
            <li
              class="page-item"
              :class="{ disabled: projectsStore.meta.current_page === projectsStore.meta.last_page }"
            >
              <button
                class="page-link"
                @click="projectsStore.fetchProjects({ ...filters, page: projectsStore.meta.current_page + 1 })"
              >
                Next
              </button>
            </li>
          </ul>
        </nav>
      </div>
    </template>

    <CreateProjectModal
      v-if="showCreateModal"
      @close="showCreateModal = false"
      @created="handleProjectCreated"
    />
  </div>
</template>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}

.cursor-pointer:hover {
  background-color: #f8f9fa;
}
</style>
