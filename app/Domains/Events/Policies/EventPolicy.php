<?php

namespace App\Domains\Events\Policies;

use App\Domains\Events\Models\Event;
use App\Models\User;
use App\Policies\Concerns\InteractsWithDomainPermissions;

class EventPolicy
{
    use InteractsWithDomainPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canViewDomain($user, 'events');
    }

    public function view(User $user, Event $event): bool
    {
        return $this->canViewDomain($user, 'events');
    }

    public function create(User $user): bool
    {
        return $this->canManageDomain($user, 'events');
    }

    public function update(User $user, Event $event): bool
    {
        return $this->canManageDomain($user, 'events');
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->canManageDomain($user, 'events');
    }

    public function manageLifecycle(User $user, Event $event): bool
    {
        return $this->canManageDomain($user, 'events');
    }
}
