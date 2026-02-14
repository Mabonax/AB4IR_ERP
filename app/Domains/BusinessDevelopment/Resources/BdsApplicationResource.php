<?php

namespace App\Domains\BusinessDevelopment\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BdsApplicationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'id_number' => $this->id_number,
            'gender' => $this->gender,
            'mobile_number' => $this->mobile_number,
            'email' => $this->email,
            'company_name' => $this->company_name,
            'company_registration_number' => $this->company_registration_number,
            'position_in_company' => $this->position_in_company,
            'majority_shareholding' => $this->majority_shareholding,
            'current_number_of_employees' => $this->current_number_of_employees,
            'physical_address' => $this->physical_address,
            'website_address' => $this->website_address,
            'years_in_operation' => $this->years_in_operation,
            'province_id' => $this->province_id,
            'province_name' => $this->province?->name,
            'has_business_plan' => $this->has_business_plan,
            'relevant_skill_set' => $this->relevant_skill_set,
            'technology_product_service' => $this->technology_product_service,
            'technology_stage_of_development' => $this->technology_stage_of_development,
            'application_date' => $this->application_date?->toDateString(),
            'assessment_status' => $this->assessment_status,
            'assessed_by_staff_id' => $this->assessed_by_staff_id,
            'assessor_name' => $this->assessor
                ? trim(($this->assessor->first_name ?? '').' '.($this->assessor->last_name ?? ''))
                : ($this->updatedBy?->name ?? null),
            'assessed_at' => $this->assessed_at?->toDateTimeString(),
            'pitch_scheduled_at' => $this->pitch_scheduled_at?->toDateTimeString(),
            'pitch_notes' => $this->pitch_notes,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
