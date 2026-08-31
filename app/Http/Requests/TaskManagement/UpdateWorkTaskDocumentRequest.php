<?php

namespace App\Http\Requests\TaskManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkTaskDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'document_kind' => ['required', Rule::in(['supporting', 'delivery', 'review_feedback', 'revised_submission', 'approval_reference'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'file' => ['nullable', 'file', 'max:51200'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max' => 'The supporting evidence file may not be larger than 50 MB.',
            'file.file' => 'Upload a valid supporting evidence file.',
        ];
    }
}
