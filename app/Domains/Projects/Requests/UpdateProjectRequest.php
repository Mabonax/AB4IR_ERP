<?php

namespace App\Domains\Projects\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'program_id' => 'required|exists:programs,id',
            'sponsor_stakeholder_id' => 'nullable|exists:stakeholders,id',
            'project_manager_id' => 'required|exists:staff_members,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'status' => 'required|in:planned,active,completed,on_hold,cancelled',
            'description' => 'nullable|string|max:1000',
        ];
    }
}
