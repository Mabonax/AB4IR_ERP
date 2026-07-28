<?php

namespace App\Domains\Intelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemoryRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('domain.intelligence.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'subject_type' => ['required', 'string', 'max:100'],
            'subject_id' => ['required', 'integer', 'min:1'],
            'memory_type' => ['required', Rule::in(config('intelligence.memory.allowed_types'))],
            'content' => ['required', 'string'],
            'confidence_score' => ['required', 'numeric', 'between:0,1'],
            'source_conversation_id' => ['nullable', 'integer', 'min:1'],
            'source_message_id' => ['nullable', 'integer', 'min:1'],
            'visibility' => ['required', Rule::in(['private', 'team', 'organization', 'global'])],
            'expires_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
