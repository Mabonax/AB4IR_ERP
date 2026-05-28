<?php

namespace App\Domains\Marketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MarketingJobCommentResource extends JsonResource
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
