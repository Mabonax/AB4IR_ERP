<?php

namespace App\Domains\Documents\Services;

use App\Domains\Documents\Models\DocumentFile;
use App\Domains\Documents\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentVersionService
{
    public function __construct(
        protected DocumentActivityService $activityService,
    ) {}

    public function recordInitialVersion(DocumentFile $document, ?string $notes = null): DocumentVersion
    {
        return DocumentVersion::query()->create([
            'document_id' => $document->id,
            'version_number' => max((int) $document->version, 1),
            'disk' => $document->disk,
            'file_path' => $document->file_path,
            'original_name' => $document->original_name,
            'mime_type' => $document->mime_type,
            'size_bytes' => $document->size_bytes,
            'uploaded_by' => $document->uploaded_by,
            'notes' => $notes ?? 'Initial version.',
        ]);
    }

    public function uploadNewVersion(DocumentFile $document, UploadedFile $file, User $actor, ?string $notes = null): DocumentFile
    {
        return DB::transaction(function () use ($document, $file, $actor, $notes) {
            $nextVersion = ((int) $document->version) + 1;
            $path = $file->storeAs(
                'document-library/'.$document->folder_id,
                Str::uuid()->toString().($file->getClientOriginalExtension() ? '.'.$file->getClientOriginalExtension() : ''),
                $document->disk
            );

            $document->forceFill([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'version' => $nextVersion,
                'uploaded_by' => $actor->id,
                'checked_out_by' => null,
                'checked_out_at' => null,
            ])->save();

            DocumentVersion::query()->create([
                'document_id' => $document->id,
                'version_number' => $nextVersion,
                'disk' => $document->disk,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'uploaded_by' => $actor->id,
                'notes' => $notes,
            ]);

            $this->activityService->record('version_created', $document->fresh(), actor: $actor, metadata: [
                'version_number' => $nextVersion,
                'notes' => $notes,
            ]);

            return $document->fresh(['versions.uploader', 'checkedOutBy']);
        });
    }

    public function restoreVersion(DocumentFile $document, DocumentVersion $version, User $actor): DocumentFile
    {
        return DB::transaction(function () use ($document, $version, $actor) {
            if ((int) $version->document_id !== (int) $document->id) {
                abort(404);
            }

            $nextVersion = ((int) $document->version) + 1;
            $restoredPath = $this->duplicateStoredFile($version);

            $document->forceFill([
                'file_path' => $restoredPath,
                'original_name' => $version->original_name,
                'mime_type' => $version->mime_type,
                'size_bytes' => $version->size_bytes,
                'version' => $nextVersion,
                'uploaded_by' => $actor->id,
                'checked_out_by' => null,
                'checked_out_at' => null,
            ])->save();

            DocumentVersion::query()->create([
                'document_id' => $document->id,
                'version_number' => $nextVersion,
                'disk' => $version->disk,
                'file_path' => $restoredPath,
                'original_name' => $version->original_name,
                'mime_type' => $version->mime_type,
                'size_bytes' => $version->size_bytes,
                'uploaded_by' => $actor->id,
                'notes' => 'Restored from version '.$version->version_number.'.',
            ]);

            $this->activityService->record('version_restored', $document->fresh(), actor: $actor, metadata: [
                'restored_from_version' => $version->version_number,
                'new_version' => $nextVersion,
            ]);

            return $document->fresh(['versions.uploader', 'checkedOutBy']);
        });
    }

    protected function duplicateStoredFile(DocumentVersion $version): string
    {
        $extension = pathinfo($version->original_name, PATHINFO_EXTENSION);
        $newPath = 'document-library/'.$version->document->folder_id.'/'.Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');

        if (Storage::disk($version->disk)->exists($version->file_path)) {
            Storage::disk($version->disk)->copy($version->file_path, $newPath);

            return $newPath;
        }

        throw new \RuntimeException('Stored version file could not be restored because the source file is missing.');
    }
}
