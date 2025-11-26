<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api
|
*/

// Auth routes (public)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/mock-users', [AuthController::class, 'mockUsers']);

    // Protected auth routes
    Route::middleware('jwt.auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// Protected API routes
Route::middleware('jwt.auth')->group(function () {
    // Projects
    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index']);
        Route::post('/', [ProjectController::class, 'store']);
        Route::get('/{id}', [ProjectController::class, 'show']);
        Route::put('/{id}', [ProjectController::class, 'update']);
        Route::delete('/{id}', [ProjectController::class, 'destroy']);
        Route::post('/{id}/restore', [ProjectController::class, 'restore']);
        Route::get('/{id}/members', [ProjectController::class, 'members']);
        Route::post('/{id}/members', [ProjectController::class, 'addMember']);
        Route::delete('/{id}/members/{memberId}', [ProjectController::class, 'removeMember']);
    });

    // My Tasks (tasks assigned to current user)
    Route::get('/my-tasks', [TaskController::class, 'myTasks']);

    // Tasks (nested under projects)
    Route::prefix('projects/{projectId}/tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::post('/', [TaskController::class, 'store']);
        Route::get('/{taskId}', [TaskController::class, 'show']);
        Route::put('/{taskId}', [TaskController::class, 'update']);
        Route::delete('/{taskId}', [TaskController::class, 'destroy']);
        Route::post('/{taskId}/restore', [TaskController::class, 'restore']);

        // Task Dependencies
        Route::get('/{taskId}/dependencies', [TaskController::class, 'dependencies']);
        Route::post('/{taskId}/dependencies', [TaskController::class, 'addDependency']);
        Route::delete('/{taskId}/dependencies/{dependencyId}', [TaskController::class, 'removeDependency']);

        // Task Comments
        Route::get('/{taskId}/comments', [TaskController::class, 'comments']);
        Route::post('/{taskId}/comments', [TaskController::class, 'addComment']);
        Route::put('/{taskId}/comments/{commentId}', [TaskController::class, 'updateComment']);
        Route::delete('/{taskId}/comments/{commentId}', [TaskController::class, 'deleteComment']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/read', [NotificationController::class, 'destroyAllRead']);
        Route::get('/{id}', [NotificationController::class, 'show']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/{id}/unread', [NotificationController::class, 'markAsUnread']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });
});
