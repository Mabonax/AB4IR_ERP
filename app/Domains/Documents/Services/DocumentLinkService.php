<?php

namespace App\Domains\Documents\Services;

use App\Domains\Assets\Models\Asset;
use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Documents\Models\DocumentFile;
use App\Domains\Documents\Models\DocumentLink;
use App\Domains\Events\Models\Event;
use App\Domains\Marketing\Models\MarketingAsset;
use App\Domains\Organization\Models\OrganizationProfile;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\Stakeholders\Models\Stakeholder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DocumentLinkService
{
    protected const RELATIONSHIP_TYPES = [
        'primary',
        'supporting',
        'reference',
        'evidence',
        'approval',
        'report',
        'contract',
    ];

    public function __construct(
        protected DocumentActivityService $activityService,
        protected DocumentAccessService $accessService,
    ) {}

    public function relationshipTypes(): array
    {
        return collect(self::RELATIONSHIP_TYPES)->map(fn (string $type) => [
            'value' => $type,
            'label' => str($type)->replace('_', ' ')->title()->toString(),
        ])->all();
    }

    public function supportedModels(): array
    {
        return [
            Program::class => 'Programs',
            Project::class => 'Projects',
            Event::class => 'Events',
            Beneficiary::class => 'Beneficiaries',
            Stakeholder::class => 'Stakeholders',
            Asset::class => 'Assets',
            MarketingAsset::class => 'Marketing Assets',
            StaffMember::class => 'Staff Members',
            OrganizationProfile::class => 'Organization',
        ];
    }

    public function link(DocumentFile $document, string $linkableType, int $linkableId, string $relationshipType, User $actor): DocumentLink
    {
        if (! in_array($relationshipType, self::RELATIONSHIP_TYPES, true)) {
            throw ValidationException::withMessages([
                'relationship_type' => ['Choose a valid relationship type.'],
            ]);
        }

        $model = $this->resolveModel($linkableType, $linkableId);

        $link = DocumentLink::query()->firstOrCreate([
            'document_id' => $document->id,
            'linkable_type' => $model::class,
            'linkable_id' => $model->getKey(),
            'relationship_type' => $relationshipType,
        ], [
            'linked_by' => $actor->id,
        ]);

        $this->activityService->record('link_created', $document, actor: $actor, entityContext: class_basename($model), metadata: [
            'relationship_type' => $relationshipType,
            'linkable_type' => $model::class,
            'linkable_id' => $model->getKey(),
        ]);

        return $link->load('linkable');
    }

    public function unlink(DocumentFile $document, DocumentLink $link, User $actor): void
    {
        if ((int) $link->document_id !== (int) $document->id) {
            abort(404);
        }

        $this->activityService->record('link_removed', $document, actor: $actor, entityContext: class_basename((string) $link->linkable_type), metadata: [
            'relationship_type' => $link->relationship_type,
            'linkable_type' => $link->linkable_type,
            'linkable_id' => $link->linkable_id,
        ]);

        $link->delete();
    }

    public function options(User $user): array
    {
        return collect($this->supportedModels())
            ->filter(fn (string $label, string $modelClass) => $this->accessService->canViewOwner($user, $modelClass) || $this->accessService->canManageOwner($user, $modelClass))
            ->map(function (string $label, string $modelClass) {
                $items = $this->itemsForModel($modelClass);

                if ($items->isEmpty()) {
                    return null;
                }

                return [
                    'label' => $label,
                    'linkable_type' => $modelClass,
                    'items' => $items->values()->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function resolveModel(string $linkableType, int $linkableId): Model
    {
        if (! array_key_exists($linkableType, $this->supportedModels())) {
            throw ValidationException::withMessages([
                'linkable_type' => ['Choose a supported link target.'],
            ]);
        }

        return $linkableType::query()->findOrFail($linkableId);
    }

    protected function itemsForModel(string $modelClass): Collection
    {
        return match ($modelClass) {
            Program::class => Program::query()->orderBy('title')->get(['id', 'title'])->map(fn (Program $model) => ['id' => $model->id, 'name' => $model->title]),
            Project::class => Project::query()->orderBy('name')->get(['id', 'name'])->map(fn (Project $model) => ['id' => $model->id, 'name' => $model->name]),
            Event::class => Event::query()->orderBy('title')->get(['id', 'title'])->map(fn (Event $model) => ['id' => $model->id, 'name' => $model->title]),
            Beneficiary::class => Beneficiary::query()->orderBy('name')->orderBy('surname')->get(['id', 'name', 'surname'])->map(fn (Beneficiary $model) => ['id' => $model->id, 'name' => trim($model->name.' '.$model->surname)]),
            Stakeholder::class => Stakeholder::query()->orderBy('organization_name')->orderBy('name')->get(['id', 'organization_name', 'name'])->map(fn (Stakeholder $model) => ['id' => $model->id, 'name' => trim(($model->organization_name ? $model->organization_name.' - ' : '').$model->name)]),
            Asset::class => Asset::query()->orderBy('name')->get(['id', 'name', 'asset_code'])->map(fn (Asset $model) => ['id' => $model->id, 'name' => trim($model->name.' '.($model->asset_code ? '('.$model->asset_code.')' : ''))]),
            MarketingAsset::class => MarketingAsset::query()->orderByDesc('id')->get(['id', 'asset_file_name'])->map(fn (MarketingAsset $model) => ['id' => $model->id, 'name' => $model->asset_file_name ?: 'Marketing Asset #'.$model->id]),
            StaffMember::class => StaffMember::query()->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn (StaffMember $model) => ['id' => $model->id, 'name' => trim($model->first_name.' '.$model->last_name)]),
            OrganizationProfile::class => OrganizationProfile::query()->get(['id', 'name'])->map(fn (OrganizationProfile $model) => ['id' => $model->id, 'name' => $model->name ?: 'Organization']),
            default => collect(),
        };
    }
}
