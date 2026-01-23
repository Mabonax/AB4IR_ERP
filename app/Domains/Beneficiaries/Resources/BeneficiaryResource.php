<?php

namespace App\Domains\Beneficiaries\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BeneficiaryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,

            // Personal info
            'name'      => $this->name,
            'surname'   => $this->surname,
            'full_name' => trim("{$this->name} {$this->surname}"),
            'dob'       => $this->dob?->format('Y-m-d'),
            'age'       => $this->age,

            // Identification
            'id_number' => $this->id_number,
            'email'     => $this->email,
            'phone'     => $this->phone,

            // Demographics
            'gender'    => $this->gender,

            // Address
            'street_address' => $this->street_address,
            'address_line_2' => $this->address_line_2,
            'city'           => $this->city,
            'postal_code'    => $this->postal_code,
            'province_id'    => $this->province_id, // ✅ FIXED

            // Education
            'highest_qualification' => $this->highest_qualification,

            // Relations
            'next_of_kin_id' => $this->next_of_kin_id,

            // Audit
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
