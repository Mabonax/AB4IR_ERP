<?php

namespace App\Domains\Committees\Policies;

use App\Domains\Committees\Models\Committee;
use App\Models\User;

class CommitteePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('domain.committees.view') || $user->can('domain.committees.manage');
    }

    public function view(User $user, Committee $committee): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('domain.committees.manage');
    }

    public function update(User $user, Committee $committee): bool
    {
        return $user->can('domain.committees.manage');
    }
}
