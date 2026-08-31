<?php

namespace App\Http\Requests\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class UploadMarketingRequestDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'document_kind' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'file' => ['required', 'file', 'max:51200'],
        ];
    }
}
