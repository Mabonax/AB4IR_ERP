<?php

namespace App\Domains\Documents\Policies;

use App\Domains\Documents\Models\DocumentFolder;
use App\Domains\Documents\Services\DocumentAccessService;
use App\Models\User;

class DocumentFolderPolicy
{
    public function __construct(
        protected DocumentAccessService $accessService,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->accessService->canViewAny($user);
    }

    public function view(User $user, DocumentFolder $folder): bool
    {
        return $this->accessService->canViewFolder($user, $folder);
    }

    public function create(User $user): bool
    {
        return $this->accessService->canViewAny($user);
    }

    public function update(User $user, DocumentFolder $folder): bool
    {
        return $this->accessService->canManageFolder($user, $folder);
    }

    public function delete(User $user, DocumentFolder $folder): bool
    {
        return $this->accessService->canManageFolder($user, $folder);
    }
}
