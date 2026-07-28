<?php

namespace App\Domains\Organisation\Policies;

use App\Domains\Organisation\Models\Organisation;
use App\Models\User;

class OrganisationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('domain.organization.view') || $user->can('domain.organization.manage');
    }

    public function view(User $user, Organisation $organisation): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('domain.organization.manage');
    }

    public function update(User $user, Organisation $organisation): bool
    {
        return $user->can('domain.organization.manage');
    }
}
