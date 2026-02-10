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
            'beneficiary_id' => $this->beneficiary_id,
            'beneficiary_name' => $this->beneficiary
                ? trim($this->beneficiary->name.' '.$this->beneficiary->surname)
                : null,
            'status' => $this->status,
            'enrolled_at' => $this->enrolled_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
