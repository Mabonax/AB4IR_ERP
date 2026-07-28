<?php

namespace App\Domains\Organisation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganisationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('domain.organization.manage') ?? false;
    }

    public function rules(): array
    {
        $organisationId = (int) $this->route('organisation');

        return [
            'name' => ['required', 'string', 'max:255'],
            'registration_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('organisations', 'registration_number')->ignore($organisationId),
            ],
            'organisation_type' => ['required', 'string', 'max:100'],
            'npo_number' => ['nullable', 'string', 'max:255'],
            'pbo_number' => ['nullable', 'string', 'max:255'],
            'tax_reference_number' => ['nullable', 'string', 'max:255'],
            'constitution_version' => ['nullable', 'string', 'max:100'],
            'registered_at' => ['nullable', 'date'],
            'npo_registered_at' => ['nullable', 'date'],
            'pbo_registered_at' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'contact_details' => ['nullable', 'array'],
            'contact_details.email' => ['nullable', 'email', 'max:255'],
            'contact_details.phone' => ['nullable', 'string', 'max:50'],
            'contact_details.contact_person' => ['nullable', 'string', 'max:255'],
            'contact_details.address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
