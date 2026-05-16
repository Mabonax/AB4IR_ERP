<?php

namespace App\Domains\BusinessDevelopment\Policies;

use App\Domains\BusinessDevelopment\Models\BdsIncubateeKpi;
use App\Models\User;

class BdsIncubateeKpiPolicy
{
    protected function canManage(User $user): bool
    {
        return $user->can('domain.business-development.manage');
    }

    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function view(User $user, BdsIncubateeKpi $kpi): bool
    {
        return $this->canManage($user);
    }

    public function assign(User $user): bool
    {
        return $this->canManage($user);
    }

    public function review(User $user, BdsIncubateeKpi $kpi): bool
    {
        return $this->canManage($user);
    }

    public function close(User $user, BdsIncubateeKpi $kpi): bool
    {
        return $this->canManage($user);
    }

    public function reopen(User $user, BdsIncubateeKpi $kpi): bool
    {
        return method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['super-admin', 'super admin', 'admin']);
    }
}
