<?php

namespace App\Domains\Facilitators\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FacilitatorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'surname' => $this->surname,
            'full_name' => trim("{$this->name} {$this->surname}"),
            'dob' => $this->dob?->format('Y-m-d'),
            'id_number' => $this->id_number,
            'address' => $this->address,
            'email' => $this->email,
            'cell' => $this->cell,
            'specialization' => $this->specialization,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
