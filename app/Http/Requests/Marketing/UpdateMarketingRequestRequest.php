<?php

namespace App\Http\Requests\Marketing;

use App\Domains\Marketing\Enums\MarketingOperationalUnit;
use App\Domains\Marketing\Enums\MarketingRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMarketingRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:12000'],
            'target_audience' => ['nullable', 'string', 'max:4000'],
            'campaign_goal' => ['nullable', 'string', 'max:4000'],
            'approver_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'owner_department_id' => ['nullable', 'integer', 'exists:staff_departments,id'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(MarketingRequestStatus::values())],
            'work_task_id' => ['nullable', 'integer', 'exists:work_tasks,id'],
            'work_package.assigned_unit' => ['nullable', Rule::in(MarketingOperationalUnit::values())],
            'work_package.operational_owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'work_package.planned_start_date' => ['nullable', 'date'],
            'work_package.planned_end_date' => ['nullable', 'date'],
        ];
    }
}
