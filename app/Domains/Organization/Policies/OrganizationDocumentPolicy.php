<?php

namespace App\Domains\Organization\Policies;

use App\Domains\Organization\Models\OrganizationDocument;
use App\Models\User;
use App\Policies\Concerns\InteractsWithDomainPermissions;

class OrganizationDocumentPolicy
{
    use InteractsWithDomainPermissions;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, OrganizationDocument $document): bool
    {
        if ($document->audience_scope === 'all_staff') {
            return true;
        }

        if ($document->audience_scope === 'department') {
            return (int) ($user->staffMember?->department_id ?? 0) === (int) $document->department_id;
        }

        return $document->targetUsers()->whereKey($user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $this->canManageVault($user);
    }

    public function update(User $user, OrganizationDocument $document): bool
    {
        return $this->canManageVault($user);
    }

    protected function canManageVault(User $user): bool
    {
        if ($this->canManageDomain($user, 'organization')) {
            $staff = $user->staffMember;

            if ($user->hasAnyRole(['super-admin', 'super admin', 'admin']) || (bool) ($staff?->is_ceo) || (bool) ($staff?->is_manager)) {
                return true;
            }
        }

        return $this->canViewDomain($user, 'marketing')
            && strtolower(trim((string) ($user->staffMember?->department?->name ?? ''))) === 'marketing';
    }
}
