<?php

namespace App\Domains\Marketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MarketingRequestDocumentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'document_kind' => $this->document_kind,
            'notes' => $this->notes,
            'file_name' => $this->file_name,
            'uploaded_by_name' => $this->uploader?->name,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
