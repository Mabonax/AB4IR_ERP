<?php

namespace App\Domains\Marketing\Policies;

use App\Domains\Marketing\Models\MarketingRequest;
use App\Domains\Marketing\Services\MarketingOperationsGovernance;
use App\Models\User;

class MarketingRequestPolicy
{
    public function __construct(
        protected MarketingOperationsGovernance $governance,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->governance->canViewDomain($user);
    }

    public function view(User $user, MarketingRequest $marketingRequest): bool
    {
        return $this->governance->canViewRequest($user, $marketingRequest);
    }

    public function create(User $user): bool
    {
        return $this->governance->canCreateRequest($user);
    }

    public function update(User $user, MarketingRequest $marketingRequest): bool
    {
        return $this->governance->canAssignDeliverables($user);
    }

    public function comment(User $user, MarketingRequest $marketingRequest): bool
    {
        return $this->governance->canViewRequest($user, $marketingRequest);
    }

    public function uploadDocument(User $user, MarketingRequest $marketingRequest): bool
    {
        return $this->governance->canViewRequest($user, $marketingRequest);
    }
}
