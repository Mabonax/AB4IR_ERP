<?php

namespace App\Domains\Organisation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganisationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'registration_number' => $this->registration_number,
            'organisation_type' => $this->organisation_type,
            'npo_number' => $this->npo_number,
            'pbo_number' => $this->pbo_number,
            'tax_reference_number' => $this->tax_reference_number,
            'constitution_version' => $this->constitution_version,
            'registered_at' => $this->registered_at?->toDateString(),
            'npo_registered_at' => $this->npo_registered_at?->toDateString(),
            'pbo_registered_at' => $this->pbo_registered_at?->toDateString(),
            'contact_details' => $this->contact_details ?? [],
            'status' => $this->status,
        ];
    }
}
