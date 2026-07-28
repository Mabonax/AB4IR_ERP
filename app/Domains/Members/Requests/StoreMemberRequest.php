<?php

namespace App\Domains\Members\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('domain.members.manage') ?? false;
    }

    public function rules(): array
    {
        return $this->memberRules();
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();

        $input['qualifications'] = $this->filterRows(
            $input['qualifications'] ?? [],
            fn (array $row): bool => $this->rowHasValue($row, [
                'qualification_type',
                'institution',
                'qualification_name',
                'field_of_study',
                'nqf_level',
                'start_date',
                'end_date',
                'completion_year',
            ]) || (bool) ($row['completed_flag'] ?? false)
        );

        $input['skills'] = $this->filterRows(
            $input['skills'] ?? [],
            fn (array $row): bool => $this->rowHasValue($row, [
                'skill_name',
                'category',
                'proficiency_level',
                'years_experience',
            ])
        );

        $input['work_experiences'] = $this->filterRows(
            $input['work_experiences'] ?? [],
            fn (array $row): bool => $this->rowHasValue($row, [
                'employer',
                'position',
                'industry',
                'start_date',
                'end_date',
                'responsibilities',
            ]) || (bool) ($row['current_employer_flag'] ?? false)
        );

        $input['interests'] = $this->filterRows(
            $input['interests'] ?? [],
            fn (array $row): bool => $this->rowHasValue($row, [
                'interest_type',
                'opportunity_category',
                'notes',
            ])
        );

        $input['assignments'] = $this->filterRows(
            $input['assignments'] ?? [],
            fn (array $row): bool => $this->rowHasValue($row, [
                'assignment_type',
                'assignable_id',
                'member_role',
                'started_at',
                'ended_at',
                'notes',
            ])
        );

        $this->replace($input);
    }

    protected function memberRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'id_number' => ['required', 'string', 'max:255', 'unique:members,id_number'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'physical_address' => ['nullable', 'string'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'township_id' => ['nullable', 'integer', 'exists:townships,id'],
            'ward_id' => ['nullable', 'integer', 'exists:wards,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'member_type' => ['required', Rule::in([
                'Community Member',
                'Volunteer',
                'Activist',
                'Beneficiary',
                'Student',
                'Graduate',
                'Professional',
                'Entrepreneur',
            ])],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended', 'deceased'])],
            'disability_status' => ['required', 'boolean'],
            'youth_indicator' => ['required', 'boolean'],
            'veteran_indicator' => ['required', 'boolean'],
            'household_size' => ['nullable', 'integer', 'min:0'],
            'dependants' => ['nullable', 'integer', 'min:0'],
            'employment' => ['nullable', 'array'],
            'employment.employment_status' => ['nullable', Rule::in([
                'Employed',
                'Unemployed',
                'Self-Employed',
                'Entrepreneur',
                'Student',
                'Internship',
                'Learnership',
                'Contract Worker',
            ])],
            'employment.employer' => ['nullable', 'string', 'max:255'],
            'employment.occupation' => ['nullable', 'string', 'max:255'],
            'employment.industry' => ['nullable', 'string', 'max:255'],
            'employment.years_experience' => ['nullable', 'integer', 'min:0'],
            'employment.monthly_income_band' => ['nullable', 'string', 'max:255'],
            'qualifications' => ['nullable', 'array'],
            'qualifications.*.qualification_type' => ['required_with:qualifications', 'string', 'max:255'],
            'qualifications.*.institution' => ['required_with:qualifications', 'string', 'max:255'],
            'qualifications.*.qualification_name' => ['required_with:qualifications', 'string', 'max:255'],
            'qualifications.*.field_of_study' => ['required_with:qualifications', 'string', 'max:255'],
            'qualifications.*.nqf_level' => ['nullable', 'string', 'max:255'],
            'qualifications.*.start_date' => ['nullable', 'date'],
            'qualifications.*.end_date' => ['nullable', 'date'],
            'qualifications.*.completed_flag' => ['required_with:qualifications', 'boolean'],
            'qualifications.*.completion_year' => ['nullable', 'integer', 'digits:4'],
            'skills' => ['nullable', 'array'],
            'skills.*.skill_name' => ['required_with:skills', 'string', 'max:255'],
            'skills.*.category' => ['nullable', 'string', 'max:255'],
            'skills.*.proficiency_level' => ['required_with:skills', Rule::in(['Beginner', 'Intermediate', 'Advanced', 'Expert'])],
            'skills.*.years_experience' => ['nullable', 'integer', 'min:0'],
            'work_experiences' => ['nullable', 'array'],
            'work_experiences.*.employer' => ['required_with:work_experiences', 'string', 'max:255'],
            'work_experiences.*.position' => ['required_with:work_experiences', 'string', 'max:255'],
            'work_experiences.*.industry' => ['nullable', 'string', 'max:255'],
            'work_experiences.*.start_date' => ['nullable', 'date'],
            'work_experiences.*.end_date' => ['nullable', 'date'],
            'work_experiences.*.current_employer_flag' => ['required_with:work_experiences', 'boolean'],
            'work_experiences.*.responsibilities' => ['nullable', 'string'],
            'interests' => ['nullable', 'array'],
            'interests.*.interest_type' => ['required_with:interests', Rule::in([
                'Learnership Interest',
                'Internship Interest',
                'Employment Interest',
                'Entrepreneurship Interest',
            ])],
            'interests.*.opportunity_category' => ['nullable', 'string', 'max:255'],
            'interests.*.notes' => ['nullable', 'string'],
            'assignments' => ['nullable', 'array'],
            'assignments.*.assignment_type' => ['required_with:assignments', Rule::in([
                'governance_structure',
                'committee',
                'branch',
                'region',
                'program',
                'project',
            ])],
            'assignments.*.assignable_id' => ['nullable', 'integer'],
            'assignments.*.member_role' => ['nullable', 'string', 'max:255'],
            'assignments.*.started_at' => ['nullable', 'date'],
            'assignments.*.ended_at' => ['nullable', 'date'],
            'assignments.*.notes' => ['nullable', 'string'],
        ];
    }

    protected function filterRows(mixed $rows, callable $shouldKeep): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn ($row) => is_array($row) ? $row : [], $rows),
            $shouldKeep
        ));
    }

    protected function rowHasValue(array $row, array $keys): bool
    {
        foreach ($keys as $key) {
            $value = $row[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return true;
            }

            if (is_numeric($value) || is_bool($value)) {
                return true;
            }
        }

        return false;
    }
}
