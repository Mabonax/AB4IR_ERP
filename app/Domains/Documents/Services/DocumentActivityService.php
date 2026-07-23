<?php

namespace App\Domains\Documents\Services;

use App\Domains\Documents\Models\DocumentActivityLog;
use App\Domains\Documents\Models\DocumentFile;
use App\Domains\Documents\Models\DocumentFolder;
use App\Models\User;

class DocumentActivityService
{
    public function record(
        string $action,
        ?DocumentFile $document = null,
        ?DocumentFolder $folder = null,
        ?User $actor = null,
        ?string $entityContext = null,
        array $metadata = [],
    ): DocumentActivityLog {
        return DocumentActivityLog::query()->create([
            'document_id' => $document?->id,
            'folder_id' => $folder?->id ?? $document?->folder_id,
            'user_id' => $actor?->id,
            'action' => $action,
            'entity_context' => $entityContext,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }
}
