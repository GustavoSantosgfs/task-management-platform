<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->attributes->get('auth_role');
        return in_array($role, ['admin', 'project_manager']);
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:planning,active,on_hold,completed',
            'visibility' => 'nullable|in:public,private',
            'manager_id' => 'nullable|integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The project title is required.',
            'title.max' => 'The project title cannot exceed 255 characters.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
            'status.in' => 'Invalid project status. Must be: planning, active, on_hold, or completed.',
            'visibility.in' => 'Invalid visibility. Must be: public or private.',
            'manager_id.exists' => 'The selected manager does not exist.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'details' => $validator->errors()->toArray(),
            ],
        ], 422));
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'FORBIDDEN',
                'message' => 'Only managers and admins can update projects.',
            ],
        ], 403));
    }
}
