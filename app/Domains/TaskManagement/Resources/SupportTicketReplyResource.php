<?php

namespace App\Domains\TaskManagement\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketReplyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'message' => $this->message,
            'is_resolution' => $this->is_resolution,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
