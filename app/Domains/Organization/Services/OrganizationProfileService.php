<?php

namespace App\Domains\Organization\Services;

use App\Domains\Organization\Models\OrganizationMetricSnapshot;
use App\Domains\Organization\Models\OrganizationProfile;
use App\Domains\Organization\Repositories\OrganizationProfileRepositoryInterface;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class OrganizationProfileService
{
    public function __construct(
        protected OrganizationProfileRepositoryInterface $repository
    ) {}

    public function getProfile(): OrganizationProfile
    {
        return $this->repository->first() ?? $this->repository->upsert([
            'name' => config('app.name', 'Programme of Action ERP'),
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

        $updated = $this->repository->upsert($data);

        $this->recordMetricSnapshotIfNeeded($updated, $profile, $data);

        return $updated;
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
        $this->backfillMetricSnapshotIfMissing($profile);

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
            'impact_history' => $profile->metricSnapshots()
                ->orderBy('captured_at')
                ->get()
                ->map(fn (OrganizationMetricSnapshot $snapshot) => $this->mapMetricSnapshot($snapshot))
                ->values()
                ->all(),
            'impact_mix' => array_values(array_filter([
                $this->mapBreakdownItem('digital', 'Digital Impact', $profile->impact_digital, '#dc2626'),
                $this->mapBreakdownItem('physical', 'Physical Impact', $profile->impact_physical, '#ea580c'),
            ])),
            'impact_channel_breakdown' => array_values(array_filter([
                $this->mapBreakdownItem('website', 'Website', $profile->impact_website, '#0f766e'),
                $this->mapBreakdownItem('walkins', 'Walk-ins', $profile->impact_walkins, '#0284c7'),
                $this->mapBreakdownItem('facebook', 'Facebook', $profile->impact_facebook, '#2563eb'),
                $this->mapBreakdownItem('x', 'X / Twitter', $profile->impact_x, '#475569'),
                $this->mapBreakdownItem('linkedin', 'LinkedIn', $profile->impact_linkedin, '#1d4ed8'),
                $this->mapBreakdownItem('livestreaming', 'Livestreaming', $profile->impact_livestreaming, '#7c3aed'),
                $this->mapBreakdownItem('instagram', 'Instagram', $profile->impact_instagram, '#db2777'),
                $this->mapBreakdownItem('youtube', 'YouTube', $profile->impact_youtube, '#b91c1c'),
            ])),
            'updated_at' => $profile->updated_at?->toDateTimeString(),
        ];
    }

    protected function backfillMetricSnapshotIfMissing(OrganizationProfile $profile): void
    {
        if (! $profile->exists || ! $this->profileHasMetrics($profile) || $profile->metricSnapshots()->exists()) {
            return;
        }

        OrganizationMetricSnapshot::query()->create($this->snapshotPayload(
            $profile,
            $profile->updated_at ?? $profile->created_at ?? now()
        ));
    }

    protected function recordMetricSnapshotIfNeeded(OrganizationProfile $profile, ?OrganizationProfile $original, array $data): void
    {
        $statColumns = $this->statColumns();
        $includesStatInput = collect($statColumns)->contains(fn (string $column) => array_key_exists($column, $data));

        if (! $includesStatInput || ! $this->profileHasMetrics($profile)) {
            return;
        }

        $shouldSnapshot = ! $original;

        if ($original) {
            foreach ($statColumns as $column) {
                if (! array_key_exists($column, $data)) {
                    continue;
                }

                if ($this->normalizeMetricValue($original->{$column}) !== $this->normalizeMetricValue($profile->{$column})) {
                    $shouldSnapshot = true;
                    break;
                }
            }
        }

        if (! $shouldSnapshot) {
            return;
        }

        OrganizationMetricSnapshot::query()->create($this->snapshotPayload($profile, now()));
    }

    protected function snapshotPayload(OrganizationProfile $profile, CarbonInterface $capturedAt): array
    {
        return [
            'organization_profile_id' => $profile->id,
            'captured_at' => $capturedAt,
            'impact_total' => $profile->impact_total,
            'impact_digital' => $profile->impact_digital,
            'impact_physical' => $profile->impact_physical,
            'trainings_conducted' => $profile->trainings_conducted,
            'impact_website' => $profile->impact_website,
            'impact_walkins' => $profile->impact_walkins,
            'impact_facebook' => $profile->impact_facebook,
            'impact_x' => $profile->impact_x,
            'impact_linkedin' => $profile->impact_linkedin,
            'impact_livestreaming' => $profile->impact_livestreaming,
            'impact_instagram' => $profile->impact_instagram,
            'impact_youtube' => $profile->impact_youtube,
        ];
    }

    protected function mapMetricSnapshot(OrganizationMetricSnapshot $snapshot): array
    {
        $capturedAt = $snapshot->captured_at ?? Carbon::now();

        return [
            'captured_at' => $capturedAt->toDateTimeString(),
            'label' => $capturedAt->format('d M H:i'),
            'total' => $snapshot->impact_total ?? 0,
            'digital' => $snapshot->impact_digital ?? 0,
            'physical' => $snapshot->impact_physical ?? 0,
            'trainings' => $snapshot->trainings_conducted ?? 0,
        ];
    }

    protected function mapBreakdownItem(string $key, string $label, ?int $value, string $fill): ?array
    {
        if ($value === null || $value < 1) {
            return null;
        }

        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'fill' => $fill,
        ];
    }

    protected function profileHasMetrics(OrganizationProfile $profile): bool
    {
        foreach ($this->statColumns() as $column) {
            if (($profile->{$column} ?? null) !== null) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeMetricValue(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    /**
     * @return array<int, string>
     */
    protected function statColumns(): array
    {
        return [
            'impact_total',
            'impact_digital',
            'impact_physical',
            'trainings_conducted',
            'impact_website',
            'impact_walkins',
            'impact_facebook',
            'impact_x',
            'impact_linkedin',
            'impact_livestreaming',
            'impact_instagram',
            'impact_youtube',
        ];
    }

    protected function logoUrl(string $variant, ?string $path): ?string
    {
        return $path ? route('organization.logos.show', ['variant' => $variant]) : null;
    }
}
