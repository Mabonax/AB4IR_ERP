<?php

namespace App\Domains\Documents\Services;

use App\Domains\Documents\Models\DocumentApproval;
use App\Domains\Documents\Models\DocumentFile;
use App\Models\User;

class DocumentApprovalService
{
    public function __construct(
        protected DocumentActivityService $activityService,
    ) {}

    public function submitForReview(DocumentFile $document, User $actor, ?string $comments = null): DocumentFile
    {
        $document->forceFill([
            'status' => 'under_review',
        ])->save();

        DocumentApproval::query()->create([
            'document_id' => $document->id,
            'approver_id' => null,
            'status' => 'under_review',
            'comments' => $comments,
        ]);

        $this->activityService->record('approval_submitted', $document, actor: $actor, metadata: [
            'comments' => $comments,
        ]);

        return $document->fresh(['approvals.approver']);
    }

    public function approve(DocumentFile $document, User $actor, ?string $comments = null): DocumentFile
    {
        $document->forceFill([
            'status' => 'approved',
        ])->save();

        DocumentApproval::query()->create([
            'document_id' => $document->id,
            'approver_id' => $actor->id,
            'status' => 'approved',
            'comments' => $comments,
            'approved_at' => now(),
        ]);

        $this->activityService->record('approved', $document, actor: $actor, metadata: [
            'comments' => $comments,
        ]);

        return $document->fresh(['approvals.approver']);
    }

    public function reject(DocumentFile $document, User $actor, ?string $comments = null): DocumentFile
    {
        $document->forceFill([
            'status' => 'draft',
        ])->save();

        DocumentApproval::query()->create([
            'document_id' => $document->id,
            'approver_id' => $actor->id,
            'status' => 'rejected',
            'comments' => $comments,
        ]);

        $this->activityService->record('rejected', $document, actor: $actor, metadata: [
            'comments' => $comments,
        ]);

        return $document->fresh(['approvals.approver']);
    }

    public function archive(DocumentFile $document, User $actor, ?string $comments = null): DocumentFile
    {
        $document->forceFill([
            'status' => 'archived',
        ])->save();

        DocumentApproval::query()->create([
            'document_id' => $document->id,
            'approver_id' => $actor->id,
            'status' => 'archived',
            'comments' => $comments,
            'approved_at' => now(),
        ]);

        $this->activityService->record('archived', $document, actor: $actor, metadata: [
            'comments' => $comments,
        ]);

        return $document->fresh(['approvals.approver']);
    }
}
