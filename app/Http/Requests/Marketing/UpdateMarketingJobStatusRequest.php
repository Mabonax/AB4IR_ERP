<?php

namespace App\Http\Requests\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMarketingJobStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:open,in_progress,blocked,cancelled'],
            'delivery_notes' => ['nullable', 'string', 'max:8000'],
        ];
    }
}
