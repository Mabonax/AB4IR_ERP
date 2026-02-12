<?php

namespace App\Domains\Stakeholders\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UpdateStakeholderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(Arr::undot($this->all()));
    }

    public function rules(): array
    {
        $stakeholderId = $this->route('stakeholder');

        return [
            'stakeholder.organization_name' => 'required|string|max:255',
            'stakeholder.name' => 'nullable|string|max:255',
            'stakeholder.email' => [
                'nullable',
                'email',
                Rule::unique('stakeholders', 'email')->ignore($stakeholderId),
            ],
            'stakeholder.contact_number' => 'nullable|string|max:20',
            'stakeholder.status' => 'required|in:active,inactive',

            'contact.full_name' => 'nullable|string|max:255',
            'contact.email' => 'nullable|email',
            'contact.contact_number' => 'nullable|string|max:20',
            'contact.position' => 'nullable|string|max:255',

            'contacts' => 'sometimes|array',
            'contacts.*.id' => 'sometimes|integer',
            'contacts.*.full_name' => 'nullable|string|max:255',
            'contacts.*.email' => 'nullable|email',
            'contacts.*.contact_number' => 'nullable|string|max:20',
            'contacts.*.position' => 'nullable|string|max:255',
        ];
    }
}
