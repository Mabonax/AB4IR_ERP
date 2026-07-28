<?php

namespace App\Domains\Intelligence\Policies;

use App\Domains\Intelligence\Models\ModelRoutingRule;
use App\Models\User;

class ModelRoutingRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('domain.intelligence.view') || $user->can('domain.intelligence.manage');
    }

    public function view(User $user, ModelRoutingRule $modelRoutingRule): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('domain.intelligence.manage');
    }

    public function update(User $user, ModelRoutingRule $modelRoutingRule): bool
    {
        return $user->can('domain.intelligence.manage');
    }
}
