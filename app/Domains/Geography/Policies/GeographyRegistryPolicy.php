<?php

namespace App\Domains\Geography\Policies;

use App\Models\User;

class GeographyRegistryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('domain.geography.view') || $user->can('domain.geography.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('domain.geography.manage');
    }
}
