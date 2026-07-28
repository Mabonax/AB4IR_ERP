<?php

namespace App\Domains\Compliance\Policies;

use App\Domains\Compliance\Models\ComplianceRecord;
use App\Models\User;

class ComplianceRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('domain.compliance.view')
            || $user->can('domain.compliance.manage')
            || $user->can('domain.organization.manage');
    }

    public function view(User $user, ComplianceRecord $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('domain.compliance.manage') || $user->can('domain.organization.manage');
    }

    public function update(User $user, ComplianceRecord $record): bool
    {
        return $this->create($user);
    }
}
