<?php

namespace App\Domains\Organization\Repositories;

use App\Domains\Organization\Models\OrganizationProfile;

interface OrganizationProfileRepositoryInterface
{
    public function first(): ?OrganizationProfile;

    public function upsert(array $data): OrganizationProfile;
}
