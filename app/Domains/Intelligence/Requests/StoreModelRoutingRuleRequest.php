<?php

namespace App\Domains\Intelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModelRoutingRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('domain.intelligence.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:150'],
            'capability' => ['required', Rule::in(['chat', 'reasoning', 'coding', 'summarization', 'embedding', 'vision', 'tool_use'])],
            'priority' => ['required', 'integer', 'min:1'],
            'max_context_tokens' => ['nullable', 'integer', 'min:1'],
            'cost_tier' => ['nullable', 'string', 'max:50'],
            'enabled' => ['nullable', 'boolean'],
            'fallback_provider' => ['nullable', 'string', 'max:100'],
            'fallback_model' => ['nullable', 'string', 'max:150'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
