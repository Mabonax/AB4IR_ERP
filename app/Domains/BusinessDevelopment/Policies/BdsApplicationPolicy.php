<?php

namespace App\Domains\BusinessDevelopment\Policies;

use App\Domains\BusinessDevelopment\Models\BdsApplication;
use App\Models\User;

class BdsApplicationPolicy
{
    protected function canViewDomain(User $user): bool
    {
        return $user->can('domain.business-development.view')
            || $user->can('domain.business-development.manage');
    }

    protected function canManageDomain(User $user): bool
    {
        return $user->can('domain.business-development.manage');
    }

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

    public function viewAny(User $user): bool
    {
        return $this->canViewDomain($user);
    }

    public function view(User $user, BdsApplication $application): bool
    {
        return $this->canViewDomain($user);
    }

    public function assess(User $user, BdsApplication $application): bool
    {
        return $this->canManageDomain($user)
            && $this->hasWorkflowRole($user)
            && $application->adjudication_result === null
            && ! $application->has_submitted_adjudication;
    }

}
