<?php

namespace App\Domains\Assets\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assignment_mode' => ['required', Rule::in(['department_staff', 'project'])],
            'department_id' => 'nullable|exists:staff_departments,id',
            'staff_member_id' => 'nullable|exists:staff_members,id',
            'project_id' => 'nullable|exists:projects,id',
            'notes' => 'nullable|string|max:3000',
        ];
    }
}
