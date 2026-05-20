<?php

namespace App\Http\Requests\TaskManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'due_date' => ['nullable', 'date'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_department_id' => ['nullable', 'integer', 'exists:staff_departments,id'],
        ];
    }
}
