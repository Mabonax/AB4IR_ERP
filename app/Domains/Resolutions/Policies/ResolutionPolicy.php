<?php

namespace App\Domains\Resolutions\Policies;

use App\Domains\Resolutions\Models\Resolution;
use App\Models\User;

class ResolutionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('domain.resolutions.view') || $user->can('domain.resolutions.manage');
    }

    public function view(User $user, Resolution $resolution): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('domain.resolutions.manage');
    }

    public function update(User $user, Resolution $resolution): bool
    {
        return $user->can('domain.resolutions.manage');
    }
}
