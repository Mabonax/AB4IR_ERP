<?php

namespace App\Domains\Stakeholders\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StakeholderResource extends JsonResource
{
    public function toArray($request): array
    {
        $primaryContact = $this->contact;

        return [
            'id' => $this->id,
            'organization_name' => $this->organization_name,
            'name' => $this->name,
            'email' => $this->email,
            'contact_number' => $this->contact_number,
            'status' => $this->status,
            'contact' => $primaryContact ? [
                'id' => $primaryContact->id,
                'full_name' => $primaryContact->full_name,
                'email' => $primaryContact->email,
                'contact_number' => $primaryContact->contact_number,
                'position' => $primaryContact->position,
            ] : null,
            'contacts' => $this->contacts->map(fn ($contact) => [
                'id' => $contact->id,
                'full_name' => $contact->full_name,
                'email' => $contact->email,
                'contact_number' => $contact->contact_number,
                'position' => $contact->position,
            ])->values(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
