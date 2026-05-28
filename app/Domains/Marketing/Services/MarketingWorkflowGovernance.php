<?php

namespace App\Domains\Marketing\Services;

use App\Domains\Marketing\Models\MarketingJob;
use App\Models\User;

class MarketingWorkflowGovernance
{
    /**
     * @return array<int, string>
     */
    public function superUserRoles(): array
    {
        return ['super-admin', 'super admin'];
    }

    public function isSuperUser(User $user): bool
    {
        return $user->hasAnyRole($this->superUserRoles());
    }

    public function belongsToMarketingDepartment(User $user): bool
    {
        if ($this->isSuperUser($user)) {
            return true;
        }

        $departmentName = strtolower(trim((string) ($user->staffMember?->department?->name ?? '')));

        return $departmentName === 'marketing';
    }

    public function isMarketingManager(User $user): bool
    {
        if ($this->isSuperUser($user)) {
            return true;
        }

        return $this->belongsToMarketingDepartment($user)
            && ((bool) $user->staffMember?->is_manager || (bool) $user->staffMember?->is_ceo);
    }

    public function canCreateJob(User $user): bool
    {
        return $this->isMarketingManager($user);
    }

    public function canManageJob(User $user, ?MarketingJob $job = null): bool
    {
        return $this->isMarketingManager($user);
    }

    public function canWorkJob(User $user, MarketingJob $job): bool
    {
        if ($this->canManageJob($user, $job)) {
            return true;
        }

        if (! $this->belongsToMarketingDepartment($user)) {
            return false;
        }

        return (int) ($job->assigned_to_user_id ?? 0) === (int) $user->id;
    }

    public function canSubmitJob(User $user, MarketingJob $job): bool
    {
        return $this->canWorkJob($user, $job);
    }

    public function canApproveJob(User $user, MarketingJob $job): bool
    {
        return $this->canManageJob($user, $job);
    }

    public function canViewJob(User $user, MarketingJob $job): bool
    {
        if ($this->canManageJob($user, $job)) {
            return true;
        }

        if ($this->belongsToMarketingDepartment($user)) {
            return true;
        }

        return (int) ($job->creator_user_id ?? 0) === (int) $user->id
            || (int) ($job->assigned_to_user_id ?? 0) === (int) $user->id;
    }
}
