<?php

namespace App\Domains\Governance\Policies;

use App\Domains\Governance\Models\GovernanceStructure;
use App\Models\User;

class GovernanceStructurePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('domain.governance.view') || $user->can('domain.governance.manage');
    }

    public function view(User $user, GovernanceStructure $structure): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('domain.governance.manage');
    }

    public function update(User $user, GovernanceStructure $structure): bool
    {
        return $user->can('domain.governance.manage');
    }
}
