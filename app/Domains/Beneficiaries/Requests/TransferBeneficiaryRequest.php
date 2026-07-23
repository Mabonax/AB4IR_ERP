<?php

namespace App\Domains\Beneficiaries\Requests;

use App\Domains\Beneficiaries\Models\BeneficiaryOutcome;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\ProjectEnrollmentConsistencyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TransferBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:1000',
            'project_id' => 'required|exists:projects,id',
            'project_location_id' => 'required|exists:project_locations,id',
            'outcome_type' => ['nullable', Rule::in(BeneficiaryOutcome::TYPES)],
            'outcome_notes' => 'nullable|string|max:2000',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('project_id')) {
                return;
            }

            $status = Project::query()->whereKey($this->input('project_id'))->value('status');

            if ($status === null || in_array($status, ProjectEnrollmentConsistencyService::BENEFICIARY_ASSIGNABLE_STATUSES, true)) {
                return;
            }

            $validator->errors()->add('project_id', 'Beneficiaries can only be transferred to planned, active, or on-hold projects.');
        });
    }
}
