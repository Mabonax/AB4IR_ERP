<?php

namespace App\Domains\Documents\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'owner_type' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'string', 'max:255'],
        ];
    }
}
