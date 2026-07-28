<?php

namespace App\Domains\Intelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('domain.intelligence.manage') ?? false;
    }

    public function rules(): array
    {
        $agentId = (int) $this->route('agent');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('agents', 'slug')->ignore($agentId)],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'active', 'disabled', 'archived'])],
            'purpose' => ['nullable', 'string'],
            'system_instructions' => ['nullable', 'string'],
            'default_provider' => ['required', 'string', 'max:100'],
            'default_model' => ['required', 'string', 'max:150'],
            'temperature' => ['nullable', 'numeric', 'between:0,2'],
            'max_tokens' => ['nullable', 'integer', 'min:1'],
            'allowed_tools' => ['nullable', 'array'],
            'allowed_knowledge_sources' => ['nullable', 'array'],
            'memory_enabled' => ['nullable', 'boolean'],
            'conversation_limit' => ['nullable', 'integer', 'min:1'],
            'visibility' => ['required', Rule::in(['private', 'team', 'organization', 'global'])],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
