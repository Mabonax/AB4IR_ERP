<?php

namespace App\Domains\Documents\Services;

use App\Domains\Documents\Models\DocumentFile;
use App\Domains\Documents\Models\DocumentFolder;
use App\Domains\Documents\Models\DocumentVersion;
use App\Domains\Documents\Repositories\DocumentFileRepositoryInterface;
use App\Domains\Organization\Models\OrganizationDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentFileService
{
    protected const DISK = 'document_library';

    public function __construct(
        protected DocumentFileRepositoryInterface $repository,
        protected DocumentAccessService $accessService,
        protected DocumentActivityService $activityService,
        protected DocumentVersionService $versionService,
    ) {}

    public function uploadFile(DocumentFolder $folder, array $data, User $actor): DocumentFile
    {
        if ($folder->isLibraryGroup()) {
            throw ValidationException::withMessages([
                'folder_id' => ['Choose a workspace folder before uploading files.'],
            ]);
        }

        if (! $this->accessService->canViewFolder($actor, $folder)) {
            abort(403);
        }

        $file = $data['file'] ?? null;

        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'file' => ['Upload a valid file.'],
            ]);
        }

        return DB::transaction(function () use ($folder, $data, $actor, $file) {
            $title = trim((string) ($data['title'] ?? ''));
            $title = $title !== '' ? $title : (string) Str::of($file->getClientOriginalName())->beforeLast('.');
            $path = $file->storeAs(
                'document-library/'.$folder->id,
                Str::uuid()->toString().($file->getClientOriginalExtension() ? '.'.$file->getClientOriginalExtension() : ''),
                self::DISK
            );

            $document = $this->repository->create([
                'folder_id' => $folder->id,
                'title' => $title,
                'description' => $data['description'] ?? null,
                'disk' => self::DISK,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'version' => 1,
                'status' => 'draft',
                'uploaded_by' => $actor->id,
            ]);

            $this->versionService->recordInitialVersion($document, 'Initial upload.');
            $this->activityService->record('uploaded', $document->fresh(), $folder, $actor, metadata: [
                'title' => $title,
                'original_name' => $file->getClientOriginalName(),
            ]);

            return $document->fresh(['folder.parent', 'uploader', 'versions.uploader', 'links.linkable', 'approvals.approver', 'activityLogs.user']);
        });
    }

    public function uploadNewVersion(DocumentFile $document, array $data, User $actor): DocumentFile
    {
        $this->assertCanMutate($document, $actor);
        $file = $data['file'] ?? null;

        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'file' => ['Upload a valid file.'],
            ]);
        }

        return $this->versionService->uploadNewVersion($document, $file, $actor, $data['notes'] ?? null);
    }

    public function restoreVersion(DocumentFile $document, DocumentVersion $version, User $actor): DocumentFile
    {
        $this->assertCanMutate($document, $actor);

        return $this->versionService->restoreVersion($document, $version, $actor);
    }

    public function moveFile(DocumentFile $file, DocumentFolder $targetFolder, User $actor): DocumentFile
    {
        $this->assertCanMutate($file, $actor);

        if (! $this->accessService->canManageFolder($actor, $targetFolder)) {
            abort(403);
        }

        if ($file->folder->owner_type !== $targetFolder->owner_type || (int) $file->folder->owner_id !== (int) $targetFolder->owner_id) {
            throw ValidationException::withMessages([
                'folder_id' => ['Files can only be moved within the same ownership scope.'],
            ]);
        }

        $updated = $this->repository->update($file, [
            'folder_id' => $targetFolder->id,
        ]);

        $this->activityService->record('moved', $updated, $targetFolder, $actor, metadata: [
            'from_folder_id' => $file->folder_id,
            'to_folder_id' => $targetFolder->id,
        ]);

        return $updated;
    }

    public function renameFile(DocumentFile $file, array $data, User $actor): DocumentFile
    {
        $this->assertCanMutate($file, $actor);

        $updated = $this->repository->update($file, [
            'title' => trim((string) $data['title']),
            'description' => $data['description'] ?? $file->description,
        ]);

        $this->activityService->record('edited', $updated, actor: $actor, metadata: [
            'title' => $updated->title,
        ]);

        return $updated;
    }

    public function deleteFile(DocumentFile $file, User $actor): void
    {
        $this->assertCanMutate($file, $actor);

        if (OrganizationDocument::query()
            ->where('source_type', DocumentFile::class)
            ->where('source_id', $file->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'file' => ['Files published to the organization vault cannot be deleted.'],
            ]);
        }

        DB::transaction(function () use ($file, $actor) {
            $this->activityService->record('deleted', $file, actor: $actor, metadata: [
                'title' => $file->title,
            ]);

            $file->versions()->get()->each(function (DocumentVersion $version) {
                Storage::disk($version->disk)->delete($version->file_path);
            });

            Storage::disk($file->disk)->delete($file->file_path);
            $this->repository->delete($file);
        });
    }

    public function downloadFile(DocumentFile $file, User $actor)
    {
        if (! $this->accessService->canViewFile($actor, $file)) {
            abort(403);
        }

        $this->activityService->record('downloaded', $file, actor: $actor);

        return Storage::disk($file->disk)->download($file->file_path, $file->original_name);
    }

    public function checkOut(DocumentFile $document, User $actor): DocumentFile
    {
        if (! $this->accessService->canCheckoutFile($actor, $document)) {
            abort(403);
        }

        if ($document->checked_out_by && (int) $document->checked_out_by !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'file' => ['This document is already checked out by another user.'],
            ]);
        }

        $document->forceFill([
            'checked_out_by' => $actor->id,
            'checked_out_at' => now(),
        ])->save();

        $this->activityService->record('checked_out', $document->fresh(), actor: $actor);

        return $document->fresh(['checkedOutBy']);
    }

    public function checkIn(DocumentFile $document, User $actor, ?string $notes = null): DocumentFile
    {
        if (! $this->accessService->canCheckoutFile($actor, $document)) {
            abort(403);
        }

        if ($document->checked_out_by && (int) $document->checked_out_by !== (int) $actor->id && ! $actor->can('documents.manage')) {
            throw ValidationException::withMessages([
                'file' => ['Only the user who checked out this document can check it in.'],
            ]);
        }

        $document->forceFill([
            'checked_out_by' => null,
            'checked_out_at' => null,
        ])->save();

        $this->activityService->record('checked_in', $document->fresh(), actor: $actor, metadata: [
            'notes' => $notes,
        ]);

        return $document->fresh(['checkedOutBy']);
    }

    public function forceRelease(DocumentFile $document, User $actor): DocumentFile
    {
        if (! $actor->can('documents.manage') && ! $this->accessService->canManageFile($actor, $document)) {
            abort(403);
        }

        $document->forceFill([
            'checked_out_by' => null,
            'checked_out_at' => null,
        ])->save();

        $this->activityService->record('force_released', $document->fresh(), actor: $actor);

        return $document->fresh(['checkedOutBy']);
    }

    protected function assertCanMutate(DocumentFile $file, User $actor): void
    {
        if (! $this->accessService->canManageFile($actor, $file)) {
            abort(403);
        }

        if ($file->checked_out_by && (int) $file->checked_out_by !== (int) $actor->id && ! $actor->can('documents.manage')) {
            throw ValidationException::withMessages([
                'file' => ['This document is checked out by another user.'],
            ]);
        }
    }
}
