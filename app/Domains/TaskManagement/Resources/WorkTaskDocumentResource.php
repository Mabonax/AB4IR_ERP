<?php

namespace App\Domains\TaskManagement\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkTaskDocumentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'document_kind' => $this->document_kind,
            'notes' => $this->notes,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'download_url' => route('task-management.tasks.documents.download', [$this->work_task_id, $this->resource]),
            'preview_url' => route('task-management.tasks.documents.preview', [$this->work_task_id, $this->resource]),
            'can_preview' => $this->isPreviewableFile($this->mime_type, $this->file_name),
            'uploaded_by_name' => $this->uploader?->name,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }

    protected function isPreviewableFile(?string $mimeType, ?string $fileName): bool
    {
        $extension = strtolower(pathinfo((string) $fileName, PATHINFO_EXTENSION));
        $mimeType = (string) $mimeType;

        return str_contains($mimeType, 'pdf')
            || str_starts_with($mimeType, 'image/')
            || str_starts_with($mimeType, 'text/')
            || in_array($extension, ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'txt', 'md', 'csv'], true);
    }
}
