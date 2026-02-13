<?php

namespace App\Domains\Projects\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectLocationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'project_name' => $this->project?->name,
            'facilitator_id' => $this->facilitator_id,
            'facilitator_name' => $this->facilitator
                ? trim($this->facilitator->name.' '.$this->facilitator->surname)
                : null,
            'province_id' => $this->province_id,
            'location' => $this->province?->name,
            'training_venue_address' => $this->training_venue_address,
            'beneficiary_count' => $this->enrollments?->count() ?? 0,
            'beneficiaries' => $this->enrollments?->map(function ($enrollment) {
                return [
                    'id' => $enrollment->beneficiary_id,
                    'name' => $enrollment->beneficiary
                        ? trim($enrollment->beneficiary->name.' '.$enrollment->beneficiary->surname)
                        : null,
                ];
            })->filter(fn ($beneficiary) => $beneficiary['name'] !== null)->values() ?? [],
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
