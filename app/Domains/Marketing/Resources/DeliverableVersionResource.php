<?php

namespace App\Domains\Marketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliverableVersionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'version_number' => $this->version_number,
            'uploaded_by_name' => $this->uploader?->name,
            'change_notes' => $this->change_notes,
            'asset_file_name' => $this->asset_file_name,
            'external_reference' => $this->external_reference,
            'approval_status' => $this->approval_status,
            'approved_by_name' => $this->approver?->name,
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
