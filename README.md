# Task Management Platform

A cloud-based Task Management & Collaboration Platform built with Laravel 12 (PHP 8.2+) and Vue 3 (TypeScript). Enables teams to manage projects, tasks, and real-time collaboration with robust access controls and notifications.

## Table of Contents

- [Project Overview](#project-overview)
- [Architecture](#architecture)
- [Tech Stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Running the Application](#running-the-application)
- [Mock Authentication](#mock-authentication)
- [API Documentation](#api-documentation)
- [Database Schema](#database-schema)
- [Design Patterns & Principles](#design-patterns--principles)
- [Security](#security)
- [Future Improvements](#future-improvements)

---

## Project Overview

This platform provides:

- **Multi-organization support** - Isolated data per organization
- **Project management** - Create, update, archive projects with team assignments
- **Task management** - Kanban-style boards with priorities, statuses, and dependencies
- **Notifications** - In-app notification center with read/unread tracking
- **Role-based access control** - Member, Project Manager, and Organization Admin roles

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         Frontend (Vue 3)                        │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────────┐ │
│  │  Views   │  │  Stores  │  │   API    │  │    Components    │ │
│  │  (Pages) │◄─┤  (Pinia) │◄─┤  Layer   │  │  (Common/Layout) │ │
│  └──────────┘  └──────────┘  └────┬─────┘  └──────────────────┘ │
└───────────────────────────────────┼─────────────────────────────┘
                                    │ HTTP/REST
┌───────────────────────────────────▼─────────────────────────────┐
│                       Backend (Laravel 12)                       │
│  ┌──────────────┐  ┌──────────────┐  ┌────────────────────────┐ │
│  │  Controllers │──│   Services   │──│     Repositories       │ │
│  │  (API Layer) │  │(Business Log)│  │    (Data Access)       │ │
│  └──────────────┘  └──────────────┘  └───────────┬────────────┘ │
│  ┌──────────────┐  ┌──────────────┐              │              │
│  │  Middleware  │  │  Resources   │              │              │
│  │  (JWT Auth)  │  │ (Transform)  │              │              │
│  └──────────────┘  └──────────────┘              │              │
└──────────────────────────────────────────────────┼──────────────┘
                                                   │
┌──────────────────────────────────────────────────▼──────────────┐
│                      Database (MySQL/SQLite)                     │
│  organizations, users, projects, tasks, notifications, etc.     │
└─────────────────────────────────────────────────────────────────┘
```

### Backend Structure

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/    # REST API endpoints
│   │   ├── Middleware/         # JWT authentication
│   │   ├── Requests/           # Form validation
│   │   └── Resources/          # JSON transformation
│   ├── Models/                 # Eloquent models
│   ├── Services/               # Business logic layer
│   ├── Repositories/           # Data access layer
│   │   └── Interfaces/         # Repository contracts
│   ├── Traits/                 # Shared functionality
│   └── Providers/              # Dependency injection
├── config/                     # App configuration
├── database/
│   ├── migrations/             # Schema definitions
│   └── seeders/                # Sample data
└── routes/api.php              # API route definitions
```

### Frontend Structure

```
frontend/src/
├── api/                        # Axios client & API modules
├── components/
│   ├── common/                 # Reusable components
│   └── layout/                 # App shell components
├── composables/                # Vue composition utilities
├── router/                     # Vue Router config
├── stores/                     # Pinia state management
├── types/                      # TypeScript interfaces
└── views/                      # Page components
```

---

## Tech Stack

### Backend
| Technology | Version | Purpose |
|------------|---------|---------|
| PHP | 8.2+ | Runtime |
| Laravel | 12.x | Framework |
| MySQL/SQLite | 8.0+/3 | Database |
| JWT | - | Authentication |

### Frontend
| Technology | Version | Purpose |
|------------|---------|---------|
| Vue.js | 3.x | UI Framework |
| TypeScript | 5.x | Type Safety |
| Pinia | 2.x | State Management |
| Vue Router | 4.x | Routing |
| Vite | 6.x | Build Tool |
| Bootstrap | 5.x | CSS Framework |

---

## Prerequisites

- **PHP** 8.2 or higher
- **Composer** 2.x
- **Node.js** 18+ and npm
- **MySQL** 8.0+ or SQLite 3
- **Git**

---

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd task-management-platform
```

### 2. Backend Setup

```bash
cd backend

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env (MySQL example):
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=task_management
# DB_USERNAME=root
# DB_PASSWORD=your_password

# Or use SQLite (simpler for development):
# DB_CONNECTION=sqlite
# DB_DATABASE=/absolute/path/to/database.sqlite

# Run migrations
php artisan migrate

# Seed the database with sample data
php artisan db:seed
```

### 3. Frontend Setup

```bash
cd frontend

# Install dependencies
npm install
```

---

## Running the Application

### Option 1: Full Stack (Recommended)

```bash
cd backend
composer dev
```

This starts concurrently:
- Laravel backend on `http://localhost:8000`
- Queue worker for background jobs
- Laravel Pail for log streaming
- Vue frontend on `http://localhost:5173`

### Option 2: Separate Terminals

**Terminal 1 - Backend:**
```bash
cd backend
php artisan serve
```

**Terminal 2 - Frontend:**
```bash
cd frontend
npm run dev
```

### Access the Application

- **Frontend:** http://localhost:5173
- **Backend API:** http://localhost:8000/api

---

## Mock Authentication

This project uses a **mocked authentication flow** for demonstration purposes. No real user registration or password hashing is implemented.

### Available Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/auth/mock-users` | List all available mock users |
| `POST` | `/api/auth/login` | Login with email/password |
| `GET` | `/api/auth/me` | Get current authenticated user |
| `POST` | `/api/auth/logout` | Logout (client-side token disposal) |

### Mock Users

All users have password: `password123`

| Email | Role | Organization | Permissions |
|-------|------|--------------|-------------|
| `admin@example.com` | Organization Admin | Org 1 | Full access to org settings, users, all projects |
| `manager@example.com` | Project Manager | Org 1 | Create projects, assign tasks, manage team |
| `member@example.com` | Member | Org 1 | View/work on assigned tasks only |
| `member2@example.com` | Member | Org 1 | View/work on assigned tasks only |
| `admin2@example.com` | Organization Admin | Org 2 | Full access (different organization) |

### Token Format

The API returns a signed **JWT token** containing:

```json
{
  "sub": 1,
  "email": "admin@example.com",
  "role": "admin",
  "orgId": 1,
  "iat": 1700000000,
  "exp": 1700003600
}
```

- **sub**: User ID
- **role**: User role (admin, project_manager, member)
- **orgId**: Organization ID (used to scope all queries)
- **exp**: Token expiration (configurable via `JWT_TTL` in .env, default 60 minutes)

### Switching Users/Roles

1. **Via UI:** On the login page, select a user from the dropdown
2. **Via API:** Call `POST /api/auth/login` with different credentials:

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "manager@example.com", "password": "password123"}'
```

### Organization Scoping

All data is automatically scoped by `orgId` from the JWT token:
- Users can only see projects within their organization
- Tasks are scoped through their parent project
- Notifications are user-specific

---

## API Documentation

### Base URL

```
http://localhost:8000/api
```

### Authentication

Include the JWT token in the Authorization header:
```
Authorization: Bearer <token>
```

### Response Format

**Success Response:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful",
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 100,
    "total_pages": 5
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": {
      "email": ["The email field is required."]
    }
  }
}
```

### Endpoints Overview

#### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/auth/mock-users` | List mock users |
| `POST` | `/auth/login` | Login |
| `GET` | `/auth/me` | Current user |
| `POST` | `/auth/logout` | Logout |

#### Projects
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/projects` | List projects (paginated) |
| `POST` | `/projects` | Create project |
| `GET` | `/projects/{id}` | Get project |
| `PUT` | `/projects/{id}` | Update project |
| `DELETE` | `/projects/{id}` | Archive project (soft delete) |
| `POST` | `/projects/{id}/restore` | Restore archived project |
| `GET` | `/projects/{id}/members` | List project members |
| `POST` | `/projects/{id}/members` | Add member |
| `DELETE` | `/projects/{id}/members/{memberId}` | Remove member |

**Project Filters:** `?status=active&visibility=public&search=keyword&include_archived=true&sort_by=created_at&sort_direction=desc`

#### Tasks
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/projects/{id}/tasks` | List tasks |
| `POST` | `/projects/{id}/tasks` | Create task |
| `GET` | `/projects/{id}/tasks/{taskId}` | Get task |
| `PUT` | `/projects/{id}/tasks/{taskId}` | Update task |
| `DELETE` | `/projects/{id}/tasks/{taskId}` | Delete task |
| `POST` | `/projects/{id}/tasks/{taskId}/restore` | Restore task |
| `GET` | `/my-tasks` | Get current user's tasks |

**Task Filters:** `?status=in_progress&priority=high&assignee_id=1&search=keyword&due_date_from=2024-01-01&due_date_to=2024-12-31`

#### Task Dependencies & Comments
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/projects/{id}/tasks/{taskId}/dependencies` | List dependencies |
| `POST` | `/projects/{id}/tasks/{taskId}/dependencies` | Add dependency |
| `DELETE` | `/projects/{id}/tasks/{taskId}/dependencies/{depId}` | Remove dependency |
| `GET` | `/projects/{id}/tasks/{taskId}/comments` | List comments |
| `POST` | `/projects/{id}/tasks/{taskId}/comments` | Add comment |
| `PUT` | `/projects/{id}/tasks/{taskId}/comments/{commentId}` | Update comment |
| `DELETE` | `/projects/{id}/tasks/{taskId}/comments/{commentId}` | Delete comment |

#### Notifications
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/notifications` | List notifications |
| `GET` | `/notifications/unread-count` | Get unread count |
| `POST` | `/notifications/mark-all-read` | Mark all as read |
| `DELETE` | `/notifications/read` | Delete all read |
| `GET` | `/notifications/{id}` | Get notification |
| `POST` | `/notifications/{id}/read` | Mark as read |
| `POST` | `/notifications/{id}/unread` | Mark as unread |
| `DELETE` | `/notifications/{id}` | Delete notification |

---

## Database Schema

### Entity Relationship Overview

```
organizations
    │
    ├──< organization_users >── users
    │
    └──< projects
            │
            ├──< project_members >── users
            │
            └──< tasks
                    │
                    ├──< task_comments
                    │
                    └──< task_dependencies (self-referencing)

notifications ──> users

activity_logs ──> (polymorphic to any model)
```

### Core Tables

| Table | Description |
|-------|-------------|
| `organizations` | Multi-tenant organization data |
| `users` | User accounts (synced from mock auth) |
| `organization_users` | User-org membership with roles |
| `projects` | Project details, status, visibility |
| `project_members` | Project-user assignments |
| `tasks` | Task data with status, priority, assignee |
| `task_comments` | Discussion comments on tasks |
| `task_dependencies` | Task-to-task dependency relationships |
| `notifications` | User notifications |
| `activity_logs` | Audit trail for all actions |

### Key Indexes

Optimized indexes for common query patterns:

- `tasks.project_id` - Task listing by project
- `tasks.status` - Kanban board filtering
- `tasks.priority` - Priority-based sorting
- `tasks(project_id, status)` - Composite for board queries
- `tasks(status, priority)` - Composite for filtered lists
- `tasks(assignee_id, status)` - My Tasks queries

---

## Design Patterns & Principles

### Patterns Used

| Pattern | Implementation |
|---------|----------------|
| **Repository Pattern** | `app/Repositories/` - Abstracts data access behind interfaces |
| **Service Layer** | `app/Services/` - Business logic separated from controllers |
| **Dependency Injection** | Via Laravel's service container and `RepositoryServiceProvider` |
| **DTO/Resource Pattern** | `app/Http/Resources/` - Transforms models for API responses |
| **Form Request Validation** | `app/Http/Requests/` - Validates and authorizes requests |

### SOLID Principles

- **Single Responsibility**: Controllers handle HTTP, Services handle logic, Repositories handle data
- **Open/Closed**: Repository interfaces allow swapping implementations
- **Liskov Substitution**: All repositories implement common interface
- **Interface Segregation**: Specific interfaces per repository
- **Dependency Inversion**: Controllers depend on service abstractions

### Frontend Architecture

- **Composition API** - Modern Vue 3 patterns
- **Pinia Stores** - Centralized state management
- **TypeScript** - Full type safety across the application
- **API Layer Abstraction** - Axios client with interceptors

---

## Security

### Implemented Measures

| Measure | Implementation |
|---------|----------------|
| **JWT Authentication** | Signed tokens with expiration |
| **CORS Configuration** | Restricted to frontend origin |
| **Input Validation** | Form Request classes validate all input |
| **SQL Injection Prevention** | Eloquent ORM with parameterized queries |
| **XSS Prevention** | Laravel's automatic output escaping |
| **Soft Deletes** | Data preservation, no hard deletes |
| **Role-Based Access** | Middleware and request authorization |

### Token Storage

Frontend stores JWT in localStorage with automatic:
- Token injection via Axios interceptor
- 401 response handling (auto-logout)
- Token refresh consideration for production

---

## Future Improvements

Given more time, the following enhancements would be prioritized:

### High Priority
1. **Unit & Integration Tests** - PHPUnit tests for services and API endpoints
2. **WebSocket Integration** - Real-time notifications via Laravel Echo
3. **API Documentation** - Swagger/OpenAPI specification with Postman collection

### Medium Priority
4. **Docker Setup** - Dockerfile and docker-compose for containerization
5. **Caching Layer** - Redis caching for frequently accessed data
6. **Activity Feed** - Expose activity logs via API

### Nice to Have
7. **@mentions** - Parse and notify mentioned users in comments
8. **File Attachments** - S3 integration for task attachments
9. **Email Notifications** - Queue-based email delivery
10. **Skeleton Loaders** - Better loading UX in frontend

---

## Running Tests

```bash
cd backend

# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test
php artisan test --filter=AuthServiceTest
```

---

## Commands Reference

### Backend

```bash
composer setup          # Full setup (install, key, migrate, seed)
composer dev            # Start all services concurrently
composer test           # Run PHPUnit tests
./vendor/bin/pint       # Format code (PSR-12)
php artisan migrate:fresh --seed  # Reset database
```

### Frontend

```bash
npm run dev             # Development server
npm run build           # Production build
npm run preview         # Preview production build
npm run type-check      # TypeScript validation
```

---

## License

This project was created as a technical challenge submission.
