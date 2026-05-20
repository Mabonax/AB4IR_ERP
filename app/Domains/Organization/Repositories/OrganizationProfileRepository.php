<?php

namespace App\Domains\Organization\Repositories;

use App\Domains\Organization\Models\OrganizationProfile;

class OrganizationProfileRepository implements OrganizationProfileRepositoryInterface
{
    public function first(): ?OrganizationProfile
    {
        return OrganizationProfile::query()->latest('id')->first();
    }

    public function upsert(array $data): OrganizationProfile
    {
        $profile = $this->first();

        if ($profile) {
            $profile->update($data);

            return $profile->fresh();
        }

        return OrganizationProfile::query()->create($data);
    }
}
