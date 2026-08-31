<?php

namespace App\Domains\Documents\Requests;

use App\Domains\Organization\Enums\OrganizationDocumentSlot;
use App\Domains\Organization\Enums\OrganizationDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadDocumentFileToVaultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'folder_id' => ['required', 'integer', 'exists:document_folders,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg,gif,webp,txt,md,csv,json', 'max:51200'],
            'document_type' => ['required', Rule::in(OrganizationDocumentType::values())],
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

    public function messages(): array
    {
        return [
            'file.required' => 'Choose a document to upload.',
            'file.file' => 'Choose a valid document file.',
            'file.mimes' => 'Only PDF, Office, image, and text files can be uploaded.',
            'file.max' => 'Documents may not be larger than 50 MB.',
            'folder_id.required' => 'Open a workspace folder before uploading a document.',
            'folder_id.exists' => 'The selected folder could not be found.',
        ];
    }
}
