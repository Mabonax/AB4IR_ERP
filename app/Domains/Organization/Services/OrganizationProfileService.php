<?php

namespace App\Domains\Organization\Services;

use App\Domains\Organization\Models\OrganizationProfile;
use App\Domains\Organization\Repositories\OrganizationProfileRepositoryInterface;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class OrganizationProfileService
{
    public function __construct(
        protected OrganizationProfileRepositoryInterface $repository
    ) {}

    public function getProfile(): OrganizationProfile
    {
        return $this->repository->first() ?? $this->repository->upsert([
            'name' => config('app.name', 'AB4IR'),
        ]);
    }

    public function updateProfile(array $data, User $actor): OrganizationProfile
    {
        $profile = $this->repository->first();

        if ($profile) {
            $data['updated_by_user_id'] = $actor->id;
        } else {
            $data['created_by_user_id'] = $actor->id;
            $data['updated_by_user_id'] = $actor->id;
        }

        return $this->repository->upsert($data);
    }

    public function updateLogos(array $files, User $actor): OrganizationProfile
    {
        $profile = $this->getProfile();
        $data = [];

        foreach ([
            'primary_logo' => 'primary_logo_path',
            'light_logo' => 'light_logo_path',
            'dark_logo' => 'dark_logo_path',
            'icon_logo' => 'icon_logo_path',
        ] as $fileKey => $column) {
            $file = $files[$fileKey] ?? null;

            if (! $file instanceof UploadedFile) {
                continue;
            }

            if ($profile->{$column}) {
                Storage::disk('public')->delete($profile->{$column});
            }

            $data[$column] = $file->store('organization/logos', 'public');
        }

        if ($data === []) {
            return $profile;
        }

        $data['updated_by_user_id'] = $actor->id;

        return $this->repository->upsert($data);
    }

    public function mapProfile(OrganizationProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'name' => $profile->name,
            'legal_name' => $profile->legal_name,
            'tagline' => $profile->tagline,
            'mission' => $profile->mission,
            'vision' => $profile->vision,
            'objectives' => $profile->objectives,
            'focus_areas' => $profile->focus_areas,
            'about' => $profile->about,
            'core_values' => $profile->core_values,
            'service_offering' => $profile->service_offering,
            'website' => $profile->website,
            'email' => $profile->email,
            'phone' => $profile->phone,
            'address_line_1' => $profile->address_line_1,
            'address_line_2' => $profile->address_line_2,
            'city' => $profile->city,
            'province' => $profile->province,
            'country' => $profile->country,
            'postal_code' => $profile->postal_code,
            'primary_logo_url' => $this->logoUrl('primary', $profile->primary_logo_path),
            'light_logo_url' => $this->logoUrl('light', $profile->light_logo_path),
            'dark_logo_url' => $this->logoUrl('dark', $profile->dark_logo_path),
            'icon_logo_url' => $this->logoUrl('icon', $profile->icon_logo_path),
            'impact_summary' => [
                'total' => $profile->impact_total,
                'digital' => $profile->impact_digital,
                'physical' => $profile->impact_physical,
                'trainings_conducted' => $profile->trainings_conducted,
            ],
            'impact_channels' => [
                ['label' => 'Website', 'value' => $profile->impact_website],
                ['label' => 'Walk-ins', 'value' => $profile->impact_walkins],
                ['label' => 'Facebook', 'value' => $profile->impact_facebook],
                ['label' => 'X / Twitter', 'value' => $profile->impact_x],
                ['label' => 'LinkedIn', 'value' => $profile->impact_linkedin],
                ['label' => 'Livestreaming', 'value' => $profile->impact_livestreaming],
                ['label' => 'Instagram', 'value' => $profile->impact_instagram],
                ['label' => 'YouTube', 'value' => $profile->impact_youtube],
            ],
            'updated_at' => $profile->updated_at?->toDateTimeString(),
        ];
    }

    protected function logoUrl(string $variant, ?string $path): ?string
    {
        return $path ? route('organization.logos.show', ['variant' => $variant]) : null;
    }
}
