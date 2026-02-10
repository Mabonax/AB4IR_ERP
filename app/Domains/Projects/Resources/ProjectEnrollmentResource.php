<?php

namespace App\Domains\Projects\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectEnrollmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'project_name' => $this->project?->name,
            'project_start_date' => $this->project?->start_date?->format('Y-m-d'),
            'beneficiary_id' => $this->beneficiary_id,
            'beneficiary_name' => $this->beneficiary
                ? trim($this->beneficiary->name.' '.$this->beneficiary->surname)
                : null,
            'project_location_id' => $this->project_location_id,
            'project_location' => $this->location?->province?->name,
            'status' => $this->status,
            'enrolled_at' => $this->enrolled_at?->toDateTimeString(),
            'project_locations' => $this->project?->locations?->map(function ($location) {
                $beneficiaries = $location->enrollments->map(function ($enrollment) {
                    return [
                        'id' => $enrollment->beneficiary_id,
                        'name' => $enrollment->beneficiary
                            ? trim($enrollment->beneficiary->name.' '.$enrollment->beneficiary->surname)
                            : null,
                    ];
                })->filter(fn ($beneficiary) => $beneficiary['name'] !== null)->values();

                return [
                    'id' => $location->id,
                    'location' => $location->province?->name,
                    'facilitator_name' => $location->facilitator
                        ? trim($location->facilitator->name.' '.$location->facilitator->surname)
                        : null,
                    'beneficiary_count' => $beneficiaries->count(),
                    'beneficiaries' => $beneficiaries,
                ];
            }) ?? [],
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
