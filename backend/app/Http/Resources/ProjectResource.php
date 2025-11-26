<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'title' => $this->title,
            'description' => $this->description,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
            'visibility' => $this->visibility,
            'manager' => new UserResource($this->whenLoaded('manager')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'members' => UserResource::collection($this->whenLoaded('members')),
            'tasks_count' => $this->when(
                $this->tasks_count !== null || $this->relationLoaded('tasks'),
                fn () => $this->tasks_count ?? $this->tasks->count()
            ),
            'completed_tasks_count' => $this->when(
                isset($this->completed_tasks_count),
                fn () => $this->completed_tasks_count
            ),
            'progress_percentage' => $this->when(
                isset($this->progress_percentage),
                fn () => $this->progress_percentage
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
