<?php

namespace App\Domains\Intelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromptTemplateRequest extends FormRequest
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
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'system_prompt' => ['nullable', 'string'],
            'developer_prompt' => ['nullable', 'string'],
            'user_prompt_template' => ['nullable', 'string'],
            'variables_schema' => ['nullable', 'array'],
            'output_schema' => ['nullable', 'array'],
            'is_default' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
