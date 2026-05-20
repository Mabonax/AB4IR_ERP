<?php

namespace App\Domains\TaskManagement\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkTaskCommentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_name' => $this->user?->name,
            'message' => $this->message,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
