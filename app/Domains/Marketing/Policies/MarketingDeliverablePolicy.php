<?php

namespace App\Domains\Marketing\Policies;

use App\Domains\Marketing\Models\MarketingDeliverable;
use App\Domains\Marketing\Services\MarketingOperationsGovernance;
use App\Models\User;

class MarketingDeliverablePolicy
{
    public function __construct(
        protected MarketingOperationsGovernance $governance,
    ) {}

    public function view(User $user, MarketingDeliverable $deliverable): bool
    {
        return $this->governance->canViewDeliverable($user, $deliverable);
    }

    public function uploadVersion(User $user, MarketingDeliverable $deliverable): bool
    {
        return $this->governance->canUploadVersion($user, $deliverable);
    }

    public function approve(User $user, MarketingDeliverable $deliverable): bool
    {
        return $this->governance->canApproveDeliverable($user, $deliverable);
    }
}
