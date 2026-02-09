<?php

namespace App\Domains\Assets\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'asset_category_id' => $this->asset_category_id,
            'category_name' => $this->category?->name,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'description' => $this->category->description,
            ] : null,
            'staff_member_id' => $this->staff_member_id,
            'staff_member' => $this->staffMember ? [
                'id' => $this->staffMember->id,
                'first_name' => $this->staffMember->first_name,
                'last_name' => $this->staffMember->last_name,
                'full_name' => trim($this->staffMember->first_name.' '.$this->staffMember->last_name),
            ] : null,
            'assigned_to' => $this->staffMember
                ? trim($this->staffMember->first_name.' '.$this->staffMember->last_name)
                : null,
            'name' => $this->name,
            'type' => $this->type,
            'serial_number' => $this->serial_number,
            'status' => $this->status,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
