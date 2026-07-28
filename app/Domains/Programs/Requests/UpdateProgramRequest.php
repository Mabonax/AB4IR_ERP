<?php

namespace App\Domains\Programs\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $programId = $this->route('program');

        return [
            'title' => 'required|string|max:255',
            'code' => ['nullable', 'string', 'max:50', Rule::unique('programs', 'code')->ignore($programId)],
            'description' => 'required|string',
            'strategic_objective' => 'nullable|string|max:4000',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:draft,planned,active,suspended,completed,cancelled',
            'budget' => 'nullable|numeric|min:0',
            'funding_source' => 'nullable|string|max:255',
            'responsible_committee_id' => 'nullable|exists:committees,id',
            'programme_manager_id' => 'nullable|exists:staff_members,id',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('programs', 'slug')->ignore($programId),
            ],
        ];
    }
}
