<?php

namespace App\Http\Requests\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class ReviewMarketingDeliverableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'review_notes' => ['required', 'string', 'max:4000'],
            'reusable' => ['nullable', 'boolean'],
        ];
    }
}
