<?php

namespace App\Domains\Intelligence\Policies;

use App\Domains\Intelligence\Models\Agent;
use App\Models\User;

class AgentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('domain.intelligence.view') || $user->can('domain.intelligence.manage');
    }

    public function view(User $user, Agent $agent): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('domain.intelligence.manage');
    }

    public function update(User $user, Agent $agent): bool
    {
        return $user->can('domain.intelligence.manage');
    }
}
