<?php

namespace App\Domains\Marketing\Services;

use App\Domains\Marketing\Models\MarketingAsset;
use App\Domains\Marketing\Models\MarketingDeliverable;
use App\Domains\Marketing\Models\MarketingRequest;
use App\Models\User;

class MarketingOperationsGovernance
{
    public function __construct(
        protected MarketingWorkflowGovernance $workflowGovernance,
    ) {}

    public function belongsToMarketing(User $user): bool
    {
        return $this->workflowGovernance->belongsToMarketingDepartment($user)
            || $user->hasAnyRole(['marketing_staff', 'graphics_staff', 'marketing_manager', 'communications_manager', 'executive_approver']);
    }

    public function canViewDomain(User $user): bool
    {
        return $user->can('domain.marketing.view') || $user->can('domain.marketing.manage');
    }

    public function canCreateRequest(User $user): bool
    {
        return $this->workflowGovernance->isMarketingManager($user)
            || $user->hasAnyRole(['marketing_manager', 'communications_manager', 'executive_approver'])
            || $user->can('marketing.requests.create');
    }

    public function canAssignDeliverables(User $user): bool
    {
        return $this->workflowGovernance->isMarketingManager($user)
            || $user->hasAnyRole(['marketing_manager', 'communications_manager'])
            || $user->can('marketing.deliverables.assign');
    }

    public function canApproveDeliverables(User $user): bool
    {
        return $this->workflowGovernance->isMarketingManager($user)
            || $user->hasAnyRole(['marketing_manager', 'communications_manager', 'executive_approver'])
            || $user->can('marketing.deliverables.approve');
    }

    public function canPublish(User $user): bool
    {
        return $this->workflowGovernance->isMarketingManager($user)
            || $user->hasAnyRole(['marketing_manager', 'communications_manager'])
            || $user->can('marketing.publications.manage');
    }

    public function canImportMetrics(User $user): bool
    {
        return $this->canPublish($user)
            || $user->can('marketing.metrics.import');
    }

    public function canArchiveAssets(User $user): bool
    {
        return $this->workflowGovernance->isMarketingManager($user)
            || $user->hasAnyRole(['marketing_manager', 'communications_manager'])
            || $user->can('marketing.assets.archive');
    }

    public function canViewPerformanceDashboard(User $user): bool
    {
        return $this->canViewDomain($user)
            && ($this->workflowGovernance->isMarketingManager($user)
                || $user->hasAnyRole(['marketing_manager', 'communications_manager', 'executive_approver'])
                || $user->can('marketing.dashboard.performance.view'));
    }

    public function canViewRequest(User $user, MarketingRequest $request): bool
    {
        if ($this->workflowGovernance->isSuperUser($user)) {
            return true;
        }

        if ($this->belongsToMarketing($user)) {
            return true;
        }

        return (int) $request->requester_user_id === (int) $user->id
            || (int) ($request->approver_user_id ?? 0) === (int) $user->id;
    }

    public function canViewDeliverable(User $user, MarketingDeliverable $deliverable): bool
    {
        if ($this->canApproveDeliverables($user) || $this->canAssignDeliverables($user)) {
            return true;
        }

        if ($this->belongsToMarketing($user)) {
            return true;
        }

        return (int) ($deliverable->assigned_to_user_id ?? 0) === (int) $user->id
            || (int) $deliverable->request?->requester_user_id === (int) $user->id;
    }

    public function canUploadVersion(User $user, MarketingDeliverable $deliverable): bool
    {
        return $this->canViewDeliverable($user, $deliverable)
            && ($this->belongsToMarketing($user) || (int) ($deliverable->assigned_to_user_id ?? 0) === (int) $user->id);
    }

    public function canApproveDeliverable(User $user, MarketingDeliverable $deliverable): bool
    {
        return $this->canApproveDeliverables($user);
    }

    public function canPublishAsset(User $user, MarketingAsset $asset): bool
    {
        return $this->canPublish($user) && ! $asset->archived_at;
    }
}
