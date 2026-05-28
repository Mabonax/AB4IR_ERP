<?php

namespace App\Domains\Marketing\Policies;

use App\Domains\Marketing\Models\MarketingJob;
use App\Domains\Marketing\Services\MarketingWorkflowGovernance;
use App\Models\User;

class MarketingJobPolicy
{
    public function __construct(
        protected MarketingWorkflowGovernance $governance,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('domain.marketing.view') || $user->can('domain.marketing.manage');
    }

    public function view(User $user, MarketingJob $job): bool
    {
        return $this->governance->canViewJob($user, $job);
    }

    public function create(User $user): bool
    {
        return $this->governance->canCreateJob($user);
    }

    public function updateStatus(User $user, MarketingJob $job): bool
    {
        return $this->governance->canWorkJob($user, $job);
    }

    public function comment(User $user, MarketingJob $job): bool
    {
        return $this->governance->canWorkJob($user, $job) || $this->governance->canManageJob($user, $job);
    }

    public function uploadDocument(User $user, MarketingJob $job): bool
    {
        return $this->comment($user, $job);
    }

    public function reassign(User $user, MarketingJob $job): bool
    {
        return $this->governance->canManageJob($user, $job);
    }

    public function submitForApproval(User $user, MarketingJob $job): bool
    {
        return $this->governance->canSubmitJob($user, $job);
    }

    public function approve(User $user, MarketingJob $job): bool
    {
        return $this->governance->canApproveJob($user, $job);
    }

    public function requestAmendments(User $user, MarketingJob $job): bool
    {
        return $this->governance->canApproveJob($user, $job);
    }
}
