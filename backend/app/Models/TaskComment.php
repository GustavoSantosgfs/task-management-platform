<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'task_comments';

    protected $fillable = [
        'task_id',
        'user_id',
        'content',
        'mentions',
    ];

    protected function casts(): array
    {
        return [
            'mentions' => 'array',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getMentionedUserIds(): array
    {
        return $this->mentions ?? [];
    }

    public function hasMentions(): bool
    {
        return !empty($this->mentions);
    }
}
