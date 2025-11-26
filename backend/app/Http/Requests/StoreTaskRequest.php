<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'assignee_id' => 'nullable|integer|exists:users,id',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'status' => 'nullable|in:todo,in_progress,in_review,done,blocked',
            'due_date' => 'nullable|date',
            'due_date_timezone' => 'nullable|string|max:50',
            'position' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Task title is required',
            'title.max' => 'Task title cannot exceed 255 characters',
            'priority.in' => 'Priority must be one of: low, medium, high, urgent',
            'status.in' => 'Status must be one of: todo, in_progress, in_review, done, blocked',
            'assignee_id.exists' => 'Selected assignee does not exist',
        ];
    }
}
