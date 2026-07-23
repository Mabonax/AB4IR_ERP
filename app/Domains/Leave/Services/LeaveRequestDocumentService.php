<?php

namespace App\Domains\Leave\Services;

use App\Domains\Documents\Models\DocumentFile;
use App\Domains\Documents\Models\DocumentFolder;
use App\Domains\Documents\Services\DocumentFileService;
use App\Domains\Leave\Models\LeaveRequest;
use App\Domains\Leave\Models\LeaveRequestDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LeaveRequestDocumentService
{
    public function __construct(
        protected DocumentFileService $documentFileService,
        protected LeaveManagementService $leaveManagementService,
    ) {}

    public function upload(LeaveRequest $leave, array $data, User $actor): LeaveRequestDocument
    {
        if (! $this->canUpload($actor, $leave)) {
            abort(403);
        }

        $folder = $this->folderForLeaveRequest($leave, $actor);

        return DB::transaction(function () use ($leave, $data, $actor, $folder) {
            $file = $this->documentFileService->uploadFile($folder, [
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'file' => $data['file'] ?? null,
            ], $actor);

            return LeaveRequestDocument::query()->create([
                'leave_request_id' => $leave->id,
                'document_file_id' => $file->id,
                'document_kind' => $data['document_kind'] ?? 'supporting_document',
                'uploaded_by' => $actor->id,
            ])->load(['documentFile.uploader', 'uploader']);
        });
    }

    public function download(LeaveRequestDocument $document, User $actor)
    {
        $leave = $document->leaveRequest()->with(['staffMember.department', 'manager'])->firstOrFail();

        if (! $this->leaveManagementService->canViewLeaveRequest($actor, $leave)) {
            abort(403);
        }

        $file = $document->documentFile;

        return Storage::disk($file->disk)->download($file->file_path, $file->original_name);
    }

    public function delete(LeaveRequestDocument $document, User $actor): void
    {
        $leave = $document->leaveRequest()->with(['staffMember.department', 'manager'])->firstOrFail();

        if (! $this->canDelete($actor, $leave, $document)) {
            abort(403);
        }

        DB::transaction(function () use ($document) {
            $file = $document->documentFile;
            $document->delete();

            if ($file && ! LeaveRequestDocument::query()->where('document_file_id', $file->id)->exists()) {
                Storage::disk($file->disk)->delete($file->file_path);
                $file->delete();
            }
        });
    }

    public function map(LeaveRequestDocument $document): array
    {
        $file = $document->documentFile;

        return [
            'id' => $document->id,
            'document_kind' => $document->document_kind,
            'title' => $file?->title,
            'description' => $file?->description,
            'original_name' => $file?->original_name,
            'mime_type' => $file?->mime_type,
            'size_bytes' => $file?->size_bytes,
            'uploaded_by_name' => $document->uploader?->name ?? $file?->uploader?->name,
            'created_at' => $document->created_at?->toDateTimeString(),
            'download_url' => route('leave-requests.documents.download', [
                'leave_request' => $document->leave_request_id,
                'document' => $document->id,
            ]),
        ];
    }

    public function canUpload(User $actor, LeaveRequest $leave): bool
    {
        return $this->isRequester($actor, $leave)
            || $this->isManager($actor, $leave)
            || $actor->can('domain.human-resources.manage')
            || $actor->can('domain.leave.manage');
    }

    public function canDelete(User $actor, LeaveRequest $leave, LeaveRequestDocument $document): bool
    {
        if ($actor->can('domain.human-resources.manage')) {
            return true;
        }

        if ((int) $document->uploaded_by === (int) $actor->id && in_array($leave->status, ['submitted', 'manager_approved'], true)) {
            return true;
        }

        return false;
    }

    protected function folderForLeaveRequest(LeaveRequest $leave, User $actor): DocumentFolder
    {
        $owner = $leave->staffMember?->user;

        if (! $owner) {
            throw ValidationException::withMessages([
                'staff' => 'This leave request is not linked to a user account for evidence storage.',
            ]);
        }

        $root = DocumentFolder::query()->firstOrCreate([
            'name' => 'Leave Evidence',
            'parent_id' => null,
            'owner_type' => User::class,
            'owner_id' => $owner->id,
        ], [
            'folder_type' => DocumentFolder::TYPE_STANDARD,
            'created_by' => $actor->id,
        ]);

        return DocumentFolder::query()->firstOrCreate([
            'name' => 'Leave Request #'.$leave->id,
            'parent_id' => $root->id,
            'owner_type' => User::class,
            'owner_id' => $owner->id,
        ], [
            'folder_type' => DocumentFolder::TYPE_STANDARD,
            'created_by' => $actor->id,
        ]);
    }

    protected function isRequester(User $actor, LeaveRequest $leave): bool
    {
        return (int) $leave->staffMember?->user_id === (int) $actor->id;
    }

    protected function isManager(User $actor, LeaveRequest $leave): bool
    {
        return (int) $actor->staffMember?->id === (int) $leave->manager_id;
    }
}
