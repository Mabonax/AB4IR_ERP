<?php

namespace App\Domains\Marketing\Policies;

use App\Domains\Marketing\Models\MarketingAsset;
use App\Domains\Marketing\Services\MarketingOperationsGovernance;
use App\Models\User;

class MarketingAssetPolicy
{
    public function __construct(
        protected MarketingOperationsGovernance $governance,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->governance->canViewDomain($user);
    }

    public function publish(User $user, MarketingAsset $asset): bool
    {
        return $this->governance->canPublishAsset($user, $asset);
    }

    public function archive(User $user, MarketingAsset $asset): bool
    {
        return $this->governance->canArchiveAssets($user);
    }
}
