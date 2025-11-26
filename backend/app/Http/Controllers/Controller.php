<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Task Management Platform API',
    description: 'A cloud-based Task Management & Collaboration Platform API. Supports project management, task tracking with Kanban boards, notifications, and role-based access control.',
    contact: new OA\Contact(email: 'support@example.com')
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: 'API Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'JWT Bearer token. Get token via POST /auth/login'
)]
#[OA\Tag(name: 'Authentication', description: 'Auth endpoints for login/logout and user info')]
#[OA\Tag(name: 'Projects', description: 'Project management endpoints')]
#[OA\Tag(name: 'Tasks', description: 'Task management endpoints')]
#[OA\Tag(name: 'Notifications', description: 'Notification management endpoints')]
abstract class Controller
{
    //
}
