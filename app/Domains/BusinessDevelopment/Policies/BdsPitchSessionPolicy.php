<?php

namespace App\Domains\BusinessDevelopment\Policies;

use App\Domains\BusinessDevelopment\Models\BdsPitchSession;
use App\Models\User;
use App\Policies\Concerns\InteractsWithDomainPermissions;

class BdsPitchSessionPolicy
{
    use InteractsWithDomainPermissions;

    protected function hasWorkflowRole(User $user): bool
    {
        return method_exists($user, 'hasAnyRole') && $user->hasAnyRole([
            'super-admin',
            'super admin',
            'admin',
            'domain-admin-business-development',
            'department-manager-business-development',
        ]);
    }

    protected function isAssignedPanelist(User $user, BdsPitchSession $session): bool
    {
        return $session->panelists()->where('user_id', $user->id)->exists();
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewDomain($user, 'business-development');
    }

    public function view(User $user, BdsPitchSession $session): bool
    {
        return $this->canViewDomain($user, 'business-development')
            && ($this->hasWorkflowRole($user) || $this->isAssignedPanelist($user, $session));
    }

    public function create(User $user): bool
    {
        return $this->canManageDomain($user, 'business-development')
            && $this->hasWorkflowRole($user);
    }

    public function start(User $user, BdsPitchSession $session): bool
    {
        return $this->create($user);
    }

    public function consolidate(User $user, BdsPitchSession $session): bool
    {
        return $this->create($user);
    }

    public function approve(User $user, BdsPitchSession $session): bool
    {
        return $this->create($user);
    }
}
