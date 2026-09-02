<?php

namespace App\Http\Requests\Marketing;

use App\Domains\Marketing\Enums\MarketingDeliverableType;
use App\Domains\Marketing\Enums\MarketingOperationalUnit;
use App\Domains\Marketing\Enums\MarketingRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMarketingRequestRequest extends FormRequest
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
            'status' => ['nullable', Rule::in(MarketingRequestStatus::values())],
            'work_task_id' => ['nullable', 'integer', 'exists:work_tasks,id'],
            'work_package.assigned_unit' => ['required', Rule::in(MarketingOperationalUnit::values())],
            'work_package.operational_owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'work_package.planned_start_date' => ['nullable', 'date'],
            'work_package.planned_end_date' => ['nullable', 'date'],
            'deliverables' => ['required', 'array', 'min:1'],
            'deliverables.*.title' => ['required', 'string', 'max:255'],
            'deliverables.*.deliverable_type' => ['required', Rule::in(MarketingDeliverableType::values())],
            'deliverables.*.assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'deliverables.*.assigned_unit' => ['required', Rule::in(MarketingOperationalUnit::values())],
            'deliverables.*.due_date' => ['nullable', 'date'],
            'deliverables.*.review_notes' => ['nullable', 'string', 'max:4000'],
            'deliverables.*.work_task_id' => ['nullable', 'integer', 'exists:work_tasks,id'],
        ];
    }
}
