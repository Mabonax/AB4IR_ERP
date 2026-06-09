<?php

namespace App\Domains\Documents\Requests;

use App\Domains\Organization\Enums\OrganizationDocumentSlot;
use App\Domains\Organization\Enums\OrganizationDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublishDocumentFileToVaultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'document_type' => ['required', Rule::in(OrganizationDocumentType::values())],
            'description' => ['nullable', 'string', 'max:4000'],
            'audience_scope' => ['required', 'in:all_staff,department,selected_users'],
            'department_id' => ['nullable', 'integer', 'exists:staff_departments,id'],
            'slot_key' => ['nullable', Rule::in(OrganizationDocumentSlot::values())],
            'replace_existing' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'selected_user_ids' => ['nullable', 'array'],
            'selected_user_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}
