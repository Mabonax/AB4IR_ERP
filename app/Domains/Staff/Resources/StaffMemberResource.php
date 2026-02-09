<?php

namespace App\Domains\Staff\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StaffMemberResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'employee_number' => $this->employee_number,
            'status' => $this->status,
            'department_id' => $this->department_id,
            'department_name' => $this->department?->name,
            'department' => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
                'description' => $this->department->description,
            ] : null,
            'next_of_kin' => $this->nextOfKin ? [
                'id' => $this->nextOfKin->id,
                'full_name' => $this->nextOfKin->full_name,
                'relationship' => $this->nextOfKin->relationship,
                'phone' => $this->nextOfKin->phone,
                'email' => $this->nextOfKin->email,
            ] : null,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
