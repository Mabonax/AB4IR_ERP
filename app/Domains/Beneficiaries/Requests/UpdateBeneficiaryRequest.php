<?php

namespace App\Domains\Beneficiaries\Requests;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Beneficiaries\Support\BeneficiaryIdentityMatcher;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\ProjectEnrollmentConsistencyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $beneficiaryId = $this->route('beneficiary');

        return [
            // =========================
            // Beneficiary
            // =========================
            'name' => 'required|string|max:100',
            'surname' => 'required|string|max:100',
            'member_id' => 'nullable|exists:members,id',
            'dob' => 'nullable|date',
            'age' => 'nullable|integer|min:0',

            'id_number' => [
                'nullable',
                'string',
                'size:13',
                Rule::unique('beneficiaries', 'id_number')->ignore($beneficiaryId),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female',
            'project_id' => 'required|exists:projects,id',
            'program_id' => 'nullable|exists:programs,id',
            'project_location_id' => 'required|exists:project_locations,id',
            'enrolment_date' => 'nullable|date',
            'exit_date' => 'nullable|date|after_or_equal:enrolment_date',
            'participation_status' => 'nullable|in:registered,enrolled,active,completed,withdrawn,suspended',
            'placement_status' => 'nullable|string|max:100',
            'member_type' => 'nullable|string|max:100',

            'street_address' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'province_id' => 'nullable|integer|exists:provinces,id',
            'postal_code' => 'nullable|string|max:20',
            'highest_qualification' => 'nullable|string|max:150',
            'attendance_status' => 'required|in:active,dropout',

            // =========================
            // Next of Kin
            // =========================
            'nok_name' => 'nullable|string|max:100',
            'nok_surname' => 'nullable|string|max:100',
            'nok_relationship' => 'nullable|string|max:100',
            'nok_phone' => 'nullable|string|max:20',
            'nok_email' => 'nullable|email',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->guardAgainstIneligibleProjectPlacement($validator);

            if (! $this->hasAnyNextOfKinInput()) {
                $this->guardAgainstDuplicateBeneficiary($validator);

                return;
            }

            foreach ([
                'nok_name' => 'Next of kin name is required when next of kin details are provided.',
                'nok_surname' => 'Next of kin surname is required when next of kin details are provided.',
                'nok_relationship' => 'Next of kin relationship is required when next of kin details are provided.',
            ] as $field => $message) {
                if ($this->filled($field)) {
                    continue;
                }

                $validator->errors()->add($field, $message);
            }

            $this->guardAgainstDuplicateBeneficiary($validator);
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalizeInputString($this->input('name')),
            'surname' => $this->normalizeInputString($this->input('surname')),
            'id_number' => $this->normalizeInputString($this->input('id_number')),
            'email' => $this->normalizeEmailInput($this->input('email')),
            'phone' => $this->normalizeInputString($this->input('phone')),
        ]);
    }

    protected function hasAnyNextOfKinInput(): bool
    {
        foreach (['nok_name', 'nok_surname', 'nok_relationship', 'nok_phone', 'nok_email'] as $field) {
            $value = $this->input($field);

            if (is_string($value) && trim($value) !== '') {
                return true;
            }
        }

        return false;
    }

    protected function guardAgainstDuplicateBeneficiary(Validator $validator): void
    {
        $candidate = $this->findDuplicateBeneficiary();

        if (! $candidate) {
            return;
        }

        $message = $candidate->trashed()
            ? 'An archived beneficiary record already matches this person. Restore or update the existing record instead of creating a duplicate.'
            : 'A matching beneficiary already exists. Update the existing record instead of creating a duplicate.';

        if ($this->filled('id_number') && $candidate->id_number === $this->input('id_number')) {
            $validator->errors()->add('id_number', $message);

            return;
        }

        $validator->errors()->add('name', $message);
    }

    protected function findDuplicateBeneficiary(): ?Beneficiary
    {
        return app(BeneficiaryIdentityMatcher::class)->findMatch($this->all(), (int) $this->route('beneficiary'));
    }

    protected function guardAgainstIneligibleProjectPlacement(Validator $validator): void
    {
        if (! $this->filled('project_id')) {
            return;
        }

        $selectedProjectId = (int) $this->input('project_id');
        $currentProjectId = (int) Beneficiary::query()->whereKey($this->route('beneficiary'))->value('project_id');

        if ($selectedProjectId === $currentProjectId) {
            return;
        }

        $status = Project::query()->whereKey($selectedProjectId)->value('status');

        if ($status === null || in_array($status, ProjectEnrollmentConsistencyService::BENEFICIARY_ASSIGNABLE_STATUSES, true)) {
            return;
        }

        $validator->errors()->add('project_id', 'Beneficiaries can only be added to planned, active, or on-hold projects.');
    }

    protected function normalizeInputString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    protected function normalizeEmailInput(mixed $value): ?string
    {
        $normalized = $this->normalizeInputString($value);

        return $normalized === null ? null : strtolower($normalized);
    }
}
