<?php

namespace App\Domains\Beneficiaries\Resources;

use App\Domains\Projects\Models\ProjectEnrollment;
use Illuminate\Support\Collection;
use Illuminate\Http\Resources\Json\JsonResource;

class BeneficiaryResource extends JsonResource
{
    public function toArray($request): array
    {
        $currentEnrollment = $this->resolveCurrentEnrollment();

        return [
            'id' => $this->id,

            // Personal info
            'name' => $this->name,
            'surname' => $this->surname,
            'full_name' => trim("{$this->name} {$this->surname}"),
            'dob' => $this->dob?->format('Y-m-d'),
            'age' => $this->age,

            // Identification
            'id_number' => $this->id_number,
            'email' => $this->email,
            'phone' => $this->phone,

            // Demographics
            'gender' => $this->gender,

            // Project
            'project_id' => $this->project_id,
            'project_name' => $this->project?->name,
            'project_location_id' => $currentEnrollment?->project_location_id,
            'project_location' => $currentEnrollment?->location?->province?->name,
            'program_id' => $this->project?->program?->id,
            'program_title' => $this->project?->program?->title,

            // Address
            'street_address' => $this->street_address,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'province_id' => $this->province_id, // ✅ FIXED

            // Education
            'highest_qualification' => $this->highest_qualification,
            'attendance_status' => $this->attendance_status ?? 'active',
            'status' => $this->status ?? 'enrolled',
            'status_reason' => $this->status_reason,
            'graduated_at' => $this->graduated_at?->toDateTimeString(),
            'exited_at' => $this->exited_at?->toDateTimeString(),
            'exit_reason' => $this->exit_reason,
            'suspended_at' => $this->suspended_at?->toDateTimeString(),
            'reactivated_at' => $this->reactivated_at?->toDateTimeString(),
            'latest_outcome' => $this->whenLoaded('latestOutcome', function () {
                if (! $this->latestOutcome) {
                    return null;
                }

                return [
                    'id' => $this->latestOutcome->id,
                    'outcome_type' => $this->latestOutcome->outcome_type,
                    'notes' => $this->latestOutcome->notes,
                    'recorded_at' => $this->latestOutcome->recorded_at?->toDateTimeString(),
                    'recorded_by_name' => $this->latestOutcome->recordedBy?->name,
                ];
            }),
            'outcomes' => $this->whenLoaded('outcomes', function () {
                return $this->outcomes->map(fn ($outcome) => [
                    'id' => $outcome->id,
                    'outcome_type' => $outcome->outcome_type,
                    'notes' => $outcome->notes,
                    'project_name' => $outcome->project?->name,
                    'program_title' => $outcome->program?->title,
                    'recorded_at' => $outcome->recorded_at?->toDateTimeString(),
                    'recorded_by_name' => $outcome->recordedBy?->name,
                ])->values();
            }),
            'timeline' => $this->whenLoaded('history', function () {
                return $this->history->map(fn ($history) => app(\App\Domains\Beneficiaries\Services\BeneficiaryHistoryService::class)->map($history))->values();
            }),

            // Relations
            'next_of_kin_id' => $this->next_of_kin_id,
            'next_of_kin' => $this->whenLoaded('nextOfKin', function () {
                return [
                    'id' => $this->nextOfKin->id,
                    'name' => $this->nextOfKin->name,
                    'surname' => $this->nextOfKin->surname,
                    'relationship' => $this->nextOfKin->relationship,
                    'phone' => $this->nextOfKin->phone,
                    'email' => $this->nextOfKin->email,
                ];
            }),
            'current_participation' => $this->whenLoaded('projectEnrollments', function () {
                $currentEnrollment = $this->resolveCurrentEnrollment();

                if (! $currentEnrollment) {
                    return null;
                }

                return [
                    'project_id' => $currentEnrollment->project_id,
                    'project_name' => $currentEnrollment->project?->name,
                    'program_id' => $currentEnrollment->project?->program?->id,
                    'program_title' => $currentEnrollment->project?->program?->title,
                    'location_id' => $currentEnrollment->project_location_id,
                    'location_name' => $currentEnrollment->location?->province?->name,
                    'status' => $currentEnrollment->status,
                    'enrolled_at' => $currentEnrollment->enrolled_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'participation_history' => $this->whenLoaded('projectEnrollments', function () {
                return $this->projectEnrollments
                    ->sortByDesc(fn ($enrollment) => optional($enrollment->enrolled_at)?->timestamp ?? 0)
                    ->values()
                    ->map(fn ($enrollment) => [
                        'id' => $enrollment->id,
                        'project_id' => $enrollment->project_id,
                        'project_name' => $enrollment->project?->name,
                        'program_id' => $enrollment->project?->program?->id,
                        'program_title' => $enrollment->project?->program?->title,
                        'location_id' => $enrollment->project_location_id,
                        'location_name' => $enrollment->location?->province?->name,
                        'status' => $enrollment->status,
                        'project_start_date' => $enrollment->project?->start_date?->format('Y-m-d'),
                        'project_end_date' => $enrollment->project?->end_date?->format('Y-m-d'),
                        'enrolled_at' => $enrollment->enrolled_at?->format('Y-m-d H:i:s'),
                    ]);
            }),

            // Audit
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
        ];
    }

    protected function resolveCurrentEnrollment(): ?ProjectEnrollment
    {
        if (! $this->relationLoaded('projectEnrollments')) {
            return null;
        }

        $enrollments = $this->sortedProjectEnrollments();

        return $enrollments->firstWhere('project_id', $this->project_id)
            ?? $enrollments->first();
    }

    protected function sortedProjectEnrollments(): Collection
    {
        return $this->projectEnrollments
            ->sortByDesc(fn ($enrollment) => optional($enrollment->enrolled_at)?->timestamp ?? 0)
            ->values();
    }
}
