// User types
export interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'project_manager' | 'member'
  organization_id: number
  organization_name: string
  created_at: string
  updated_at: string
}

// Organization types
export interface Organization {
  id: number
  name: string
  slug: string
  created_at: string
  updated_at: string
}

// Project types
export interface Project {
  id: number
  organization_id: number
  title: string
  description: string | null
  status: ProjectStatus
  visibility: 'public' | 'private'
  start_date: string | null
  end_date: string | null
  manager: User | null
  members: User[]
  tasks_count: number
  completed_tasks_count: number
  progress_percentage: number
  created_at: string
  updated_at: string
  deleted_at: string | null
}

export type ProjectStatus = 'planning' | 'active' | 'on_hold' | 'completed' | 'cancelled'

export interface CreateProjectData {
  title: string
  description?: string
  manager_id?: number
  visibility?: 'public' | 'private'
  status?: ProjectStatus
  start_date?: string
  end_date?: string
}

export interface UpdateProjectData extends Partial<CreateProjectData> {}

// Task types
export interface Task {
  id: number
  project_id: number
  title: string
  description: string | null
  priority: TaskPriority
  status: TaskStatus
  due_date: string | null
  due_date_timezone: string | null
  position: number
  is_overdue: boolean
  is_done: boolean
  is_blocked: boolean
  has_uncompleted_dependencies?: boolean
  assignee: User | null
  creator: User | null
  updater: User | null
  project?: Project
  comments?: TaskComment[]
  comments_count?: number
  dependencies?: Task[]
  created_at: string
  updated_at: string
  deleted_at: string | null
}

export type TaskStatus = 'todo' | 'in_progress' | 'review' | 'done' | 'blocked'
export type TaskPriority = 'low' | 'medium' | 'high' | 'urgent'

export interface CreateTaskData {
  title: string
  description?: string
  assignee_id?: number
  priority?: TaskPriority
  status?: TaskStatus
  due_date?: string
  due_date_timezone?: string
  position?: number
}

export interface UpdateTaskData extends Partial<CreateTaskData> {}

// Task Comment types
export interface TaskComment {
  id: number
  task_id: number
  content: string
  mentions: number[] | null
  user: User
  created_at: string
  updated_at: string
}

// Notification types
export interface Notification {
  id: number
  type: NotificationType
  title: string
  message: string
  data: Record<string, unknown>
  is_read: boolean
  read_at: string | null
  created_at: string
  updated_at: string
}

export type NotificationType =
  | 'task_assigned'
  | 'task_comment'
  | 'task_status_changed'
  | 'mention'
  | 'project_invite'
  | 'task_due_soon'

// API Response types
export interface ApiResponse<T> {
  success: boolean
  data: T
  message: string
  code?: string
}

export interface PaginatedResponse<T> {
  success: boolean
  data: T[]
  message: string
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number
    to: number
  }
}

// Auth types
export interface LoginCredentials {
  email: string
  password: string
}

export interface AuthResponse {
  user: User
  token: string
  token_type: string
  expires_in: number
}

// Filter types
export interface ProjectFilters {
  status?: ProjectStatus
  visibility?: 'public' | 'private'
  manager_id?: number
  search?: string
  include_archived?: boolean
  sort_by?: string
  sort_direction?: 'asc' | 'desc'
  per_page?: number
  page?: number
}

export interface TaskFilters {
  status?: TaskStatus
  priority?: TaskPriority
  assignee_id?: number
  search?: string
  due_date_from?: string
  due_date_to?: string
  include_archived?: boolean
  sort_by?: string
  sort_direction?: 'asc' | 'desc'
  per_page?: number
  page?: number
}

export interface NotificationFilters {
  unread_only?: boolean
  read_only?: boolean
  type?: NotificationType
  sort_by?: string
  sort_direction?: 'asc' | 'desc'
  per_page?: number
  page?: number
}
