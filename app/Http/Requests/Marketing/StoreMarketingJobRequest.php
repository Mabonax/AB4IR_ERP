<?php

namespace App\Http\Requests\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarketingJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'brief' => ['nullable', 'string', 'max:8000'],
            'job_type' => ['required', 'in:graphic_design,social_media,content_plan,letter_communication,email_signature,other'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'due_date' => ['nullable', 'date'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_department_id' => ['nullable', 'integer', 'exists:staff_departments,id'],
        ];
    }
}
