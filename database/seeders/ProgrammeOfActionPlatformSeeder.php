<?php

namespace Database\Seeders;

use App\Domains\Organisation\Models\Organisation;
use App\Domains\Organization\Models\OrganizationProfile;
use Illuminate\Database\Seeder;

class ProgrammeOfActionPlatformSeeder extends Seeder
{
    public function run(): void
    {
        OrganizationProfile::query()->firstOrCreate(
            ['name' => config('app.name')],
            [
                'legal_name' => 'Programme of Action NPC',
                'tagline' => config('branding.tagline'),
                'email' => config('branding.support_email'),
            ]
        );

        Organisation::query()->firstOrCreate(
            ['registration_number' => 'POA-NPC-PRIMARY'],
            [
                'name' => 'Programme of Action',
                'organisation_type' => 'NPC',
                'status' => 'active',
                'contact_details' => [
                    'email' => config('branding.support_email'),
                ],
            ]
        );
    }
}
