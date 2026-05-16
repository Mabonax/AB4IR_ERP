<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait InteractsWithDomainPermissions
{
    protected function canViewDomain(User $user, string $domain): bool
    {
        return $user->can("domain.{$domain}.view")
            || $user->can("domain.{$domain}.manage");
    }

    protected function canManageDomain(User $user, string $domain): bool
    {
        return $user->can("domain.{$domain}.manage");
    }
}
