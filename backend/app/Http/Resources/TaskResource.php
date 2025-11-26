<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'due_date' => $this->due_date?->toIso8601String(),
            'due_date_timezone' => $this->due_date_timezone,
            'position' => $this->position,
            'is_overdue' => $this->isOverdue(),
            'is_done' => $this->isDone(),
            'is_blocked' => $this->isBlocked(),
            'has_uncompleted_dependencies' => $this->when(
                $this->relationLoaded('dependencies'),
                fn() => $this->hasUncompletedDependencies()
            ),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'updater' => new UserResource($this->whenLoaded('updater')),
            'project' => new ProjectResource($this->whenLoaded('project')),
            'comments' => TaskCommentResource::collection($this->whenLoaded('comments')),
            'comments_count' => $this->when(isset($this->comments_count), $this->comments_count),
            'dependencies' => TaskResource::collection($this->whenLoaded('dependencies')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->when($this->deleted_at !== null, $this->deleted_at?->toIso8601String()),
        ];
    }
}
