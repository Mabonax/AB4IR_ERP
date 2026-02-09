<?php

namespace App\Domains\Stakeholders\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StakeholderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'organization_name' => $this->organization_name,
            'name' => $this->name,
            'email' => $this->email,
            'contact_number' => $this->contact_number,
            'status' => $this->status,
            'contact' => $this->contact ? [
                'id' => $this->contact->id,
                'full_name' => $this->contact->full_name,
                'email' => $this->contact->email,
                'contact_number' => $this->contact->contact_number,
                'position' => $this->contact->position,
            ] : null,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
