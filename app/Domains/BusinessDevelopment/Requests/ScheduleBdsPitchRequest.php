<?php

namespace App\Domains\BusinessDevelopment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleBdsPitchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pitch_scheduled_at' => 'required|date',
            'pitch_notes' => 'nullable|string|max:2000',
        ];
    }
}

