<?php

namespace App\Domains\Facilitators\Policies;

use App\Domains\Facilitators\Models\Facilitator;
use App\Models\User;
use App\Policies\Concerns\InteractsWithDomainPermissions;

class FacilitatorPolicy
{
    use InteractsWithDomainPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canViewDomain($user, 'facilitators');
    }

    public function view(User $user, Facilitator $facilitator): bool
    {
        return $this->canViewDomain($user, 'facilitators');
    }

    public function create(User $user): bool
    {
        return $this->canManageDomain($user, 'facilitators');
    }

    public function update(User $user, Facilitator $facilitator): bool
    {
        return $this->canManageDomain($user, 'facilitators');
    }

    public function delete(User $user, Facilitator $facilitator): bool
    {
        return $this->canManageDomain($user, 'facilitators');
    }
}
