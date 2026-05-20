<?php

namespace App\Domains\Assets\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportAssetFaultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
        ];
    }
}
