<?php

namespace App\Domains\Intelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RunConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('domain.intelligence.view') || $this->user()?->can('domain.intelligence.manage');
    }

    public function rules(): array
    {
        return [
            'conversation_id' => ['nullable', 'integer', 'min:1'],
            'agent_slug' => ['nullable', 'string', 'max:255'],
            'subject_type' => ['nullable', 'string', 'max:100'],
            'subject_id' => ['nullable', 'integer', 'min:1'],
            'message' => ['required', 'string'],
        ];
    }
}
