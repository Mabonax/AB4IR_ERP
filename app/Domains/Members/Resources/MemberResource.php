<?php

namespace App\Domains\Members\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => trim("{$this->first_name} {$this->last_name}"),
            'id_number' => $this->id_number,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'phone' => $this->phone,
            'email' => $this->email,
            'physical_address' => $this->physical_address,
            'province_id' => $this->province_id,
            'province_name' => $this->province?->name,
            'municipality_id' => $this->municipality_id,
            'municipality_name' => $this->municipality?->name,
            'region_id' => $this->region_id,
            'region_name' => $this->region?->name,
            'township_id' => $this->township_id,
            'township_name' => $this->township?->name,
            'ward_id' => $this->ward_id,
            'ward_name' => $this->ward?->name,
            'branch_id' => $this->branch_id,
            'branch_name' => $this->branch?->name,
            'member_type' => $this->member_type,
            'status' => $this->status,
            'disability_status' => (bool) $this->disability_status,
            'youth_indicator' => (bool) $this->youth_indicator,
            'veteran_indicator' => (bool) $this->veteran_indicator,
            'household_size' => $this->household_size,
            'dependants' => $this->dependants,
            'employment' => $this->whenLoaded('employmentProfile', fn () => $this->employmentProfile ? [
                'employment_status' => $this->employmentProfile->employment_status,
                'employer' => $this->employmentProfile->employer,
                'occupation' => $this->employmentProfile->occupation,
                'industry' => $this->employmentProfile->industry,
                'years_experience' => $this->employmentProfile->years_experience,
                'monthly_income_band' => $this->employmentProfile->monthly_income_band,
            ] : null),
            'qualifications' => $this->whenLoaded('qualifications', fn () => $this->qualifications->map(fn ($qualification) => [
                'qualification_type' => $qualification->qualification_type,
                'institution' => $qualification->institution,
                'qualification_name' => $qualification->qualification_name,
                'field_of_study' => $qualification->field_of_study,
                'nqf_level' => $qualification->nqf_level,
                'start_date' => $qualification->start_date?->format('Y-m-d'),
                'end_date' => $qualification->end_date?->format('Y-m-d'),
                'completed_flag' => (bool) $qualification->completed_flag,
                'completion_year' => $qualification->completion_year,
            ])->values()->all()),
            'skills' => $this->whenLoaded('skills', fn () => $this->skills->map(fn ($skill) => [
                'skill_name' => $skill->skill_name,
                'category' => $skill->category,
                'proficiency_level' => $skill->proficiency_level,
                'years_experience' => $skill->years_experience,
            ])->values()->all()),
            'work_experiences' => $this->whenLoaded('workExperiences', fn () => $this->workExperiences->map(fn ($experience) => [
                'employer' => $experience->employer,
                'position' => $experience->position,
                'industry' => $experience->industry,
                'start_date' => $experience->start_date?->format('Y-m-d'),
                'end_date' => $experience->end_date?->format('Y-m-d'),
                'current_employer_flag' => (bool) $experience->current_employer_flag,
                'responsibilities' => $experience->responsibilities,
            ])->values()->all()),
            'interests' => $this->whenLoaded('opportunityInterests', fn () => $this->opportunityInterests->map(fn ($interest) => [
                'interest_type' => $interest->interest_type,
                'opportunity_category' => $interest->opportunity_category,
                'notes' => $interest->notes,
            ])->values()->all()),
            'assignments' => $this->whenLoaded('assignments', fn () => $this->assignments->map(fn ($assignment) => [
                'assignment_type' => $assignment->assignment_type,
                'assignable_id' => $assignment->assignable_id,
                'member_role' => $assignment->member_role,
                'started_at' => $assignment->started_at?->format('Y-m-d'),
                'ended_at' => $assignment->ended_at?->format('Y-m-d'),
                'notes' => $assignment->notes,
                'assignable_name' => $assignment->assignable->name
                    ?? $assignment->assignable->title
                    ?? null,
            ])->values()->all()),
        ];
    }
}
