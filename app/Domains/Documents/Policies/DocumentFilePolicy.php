<?php

namespace App\Domains\Documents\Policies;

use App\Domains\Documents\Models\DocumentFile;
use App\Domains\Documents\Services\DocumentAccessService;
use App\Models\User;

class DocumentFilePolicy
{
    public function __construct(
        protected DocumentAccessService $accessService,
    ) {}

    public function view(User $user, DocumentFile $file): bool
    {
        return $this->accessService->canViewFile($user, $file);
    }

    public function update(User $user, DocumentFile $file): bool
    {
        return $this->accessService->canManageFile($user, $file);
    }

    public function delete(User $user, DocumentFile $file): bool
    {
        return $this->accessService->canManageFile($user, $file);
    }
}
