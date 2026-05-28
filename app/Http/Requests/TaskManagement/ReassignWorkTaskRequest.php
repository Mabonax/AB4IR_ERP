<?php

namespace App\Http\Requests\TaskManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReassignWorkTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_department_id' => ['nullable', 'integer', 'exists:staff_departments,id'],
            'reason' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(['open', 'in_progress', 'blocked', 'pending_review', 'changes_requested', 'completed', 'cancelled'])],
        ];
    }
}
