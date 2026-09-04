<?php

namespace App\Domains\Events\Policies;

use App\Domains\Events\Models\EventSeries;
use App\Models\User;
use App\Policies\Concerns\InteractsWithDomainPermissions;

class EventSeriesPolicy
{
    use InteractsWithDomainPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canViewDomain($user, 'events');
    }

    public function view(User $user, EventSeries $eventSeries): bool
    {
        return $this->canViewDomain($user, 'events');
    }

    public function create(User $user): bool
    {
        return $this->canManageDomain($user, 'events');
    }

    public function update(User $user, EventSeries $eventSeries): bool
    {
        return $this->canManageDomain($user, 'events');
    }

    public function delete(User $user, EventSeries $eventSeries): bool
    {
        return $this->canManageDomain($user, 'events');
    }

    public function createIteration(User $user, EventSeries $eventSeries): bool
    {
        return $this->canManageDomain($user, 'events');
    }
}
