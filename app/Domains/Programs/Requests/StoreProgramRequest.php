<?php

namespace App\Domains\Programs\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:programs,code',
            'description' => 'required|string',
            'strategic_objective' => 'nullable|string|max:4000',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:draft,planned,active,suspended,completed,cancelled',
            'budget' => 'nullable|numeric|min:0',
            'funding_source' => 'nullable|string|max:255',
            'responsible_committee_id' => 'nullable|exists:committees,id',
            'programme_manager_id' => 'nullable|exists:staff_members,id',
            'slug' => 'nullable|string|max:255|unique:programs,slug',
        ];
    }
}
