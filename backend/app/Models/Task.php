<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'assignee_id',
        'created_by',
        'updated_by',
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'due_date_timezone',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->orderBy('created_at', 'asc');
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'task_id',
            'depends_on_task_id'
        )->withTimestamps();
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'depends_on_task_id',
            'task_id'
        )->withTimestamps();
    }

    public function taskDependencies(): HasMany
    {
        return $this->hasMany(TaskDependency::class);
    }

    public function isOverdue(): bool
    {
        if (!$this->due_date) {
            return false;
        }
        return $this->due_date->isPast() && !in_array($this->status, ['done', 'blocked']);
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    public function hasDependencies(): bool
    {
        return $this->dependencies()->exists();
    }

    public function hasUncompletedDependencies(): bool
    {
        return $this->dependencies()->where('status', '!=', 'done')->exists();
    }
}
