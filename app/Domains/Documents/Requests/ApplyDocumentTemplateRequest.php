<?php

namespace App\Domains\Documents\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyDocumentTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'template_id' => ['required', 'integer', 'exists:document_repository_templates,id'],
        ];
    }
}
