<?php

namespace App\Domains\Events\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:2000',
            'budget_summary' => 'nullable|string|max:12000',
            'outcomes_achieved' => 'required|string|max:12000',
            'lessons_learned' => 'required|string|max:12000',
            'risks_encountered' => 'required|string|max:12000',
            'recommendations' => 'required|string|max:12000',
        ];
    }
}
