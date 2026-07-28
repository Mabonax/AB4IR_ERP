<?php

namespace App\Domains\Members\Policies;

use App\Models\User;

class MemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('domain.members.view') || $user->can('domain.members.manage');
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('domain.members.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('domain.members.manage');
    }
}
