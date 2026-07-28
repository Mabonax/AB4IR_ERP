<?php

namespace App\Domains\Resolutions\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResolutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organisation_id' => ['required', 'integer', 'exists:organisations,id'],
            'meeting_id' => ['required', 'integer', 'exists:meetings,id'],
            'resolution_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('resolutions')->where(fn ($query) => $query->where('organisation_id', $this->integer('organisation_id'))),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['open', 'in_progress', 'completed', 'overdue'])],
        ];
    }
}
