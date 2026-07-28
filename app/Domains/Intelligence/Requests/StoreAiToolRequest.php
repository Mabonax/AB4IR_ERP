<?php

namespace App\Domains\Intelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAiToolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('domain.intelligence.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'handler_class' => ['required', 'string', 'max:255'],
            'input_schema' => ['nullable', 'array'],
            'output_schema' => ['nullable', 'array'],
            'status' => ['required', Rule::in(['draft', 'active', 'disabled', 'archived'])],
            'requires_approval' => ['nullable', 'boolean'],
            'permission_key' => ['nullable', 'string', 'max:255'],
            'timeout_seconds' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
