<?php

namespace App\Domains\Compliance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplianceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organisation_id' => $this->organisation_id,
            'organisation_name' => $this->organisation?->name,
            'title' => $this->title,
            'compliance_area' => $this->compliance_area,
            'reference_code' => $this->reference_code,
            'filing_frequency' => $this->filing_frequency,
            'due_date' => $this->due_date?->toDateString(),
            'submitted_at' => $this->submitted_at?->toDateString(),
            'status' => $this->status,
            'owner_name' => $this->owner_name,
            'notes' => $this->notes,
        ];
    }
}
