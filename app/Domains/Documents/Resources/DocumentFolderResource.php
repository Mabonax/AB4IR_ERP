<?php

namespace App\Domains\Documents\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DocumentFolderResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'parent_id' => $this->parent_id,
            'owner_type' => $this->owner_type,
            'owner_id' => $this->owner_id,
            'folder_type' => $this->folder_type,
            'created_at' => $this->created_at?->toDateTimeString(),
            'can' => [
                'manage' => $user?->can('update', $this->resource) ?? false,
                'delete' => $user?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
