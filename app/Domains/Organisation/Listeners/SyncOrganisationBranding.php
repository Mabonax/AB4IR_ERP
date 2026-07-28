<?php

namespace App\Domains\Organisation\Listeners;

use App\Domains\Organisation\Events\OrganisationRegistered;
use App\Domains\Organization\Models\OrganizationProfile;

class SyncOrganisationBranding
{
    public function handle(OrganisationRegistered $event): void
    {
        OrganizationProfile::query()->updateOrCreate(
            ['name' => config('app.name')],
            [
                'legal_name' => $event->organisation->name,
            ]
        );
    }
}
