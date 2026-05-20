<?php

namespace App\Domains\Organization\Policies;

use App\Domains\Organization\Models\OrganizationProfile;
use App\Models\User;
use App\Policies\Concerns\InteractsWithDomainPermissions;

class OrganizationProfilePolicy
{
    use InteractsWithDomainPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canViewDomain($user, 'organization');
    }

    public function view(User $user, OrganizationProfile $profile): bool
    {
        return $this->canViewDomain($user, 'organization');
    }

    public function update(User $user, OrganizationProfile $profile): bool
    {
        if (! $this->canManageDomain($user, 'organization')) {
            return false;
        }

        $staff = $user->staffMember;

        return $user->hasAnyRole(['super-admin', 'super admin', 'admin'])
            || (bool) ($staff?->is_ceo)
            || (bool) ($staff?->is_manager);
    }
}
