<?php

namespace App\Domains\Documents\Services;

use App\Domains\Documents\Models\DocumentFolder;
use App\Domains\Documents\Models\DocumentRepositoryTemplate;
use App\Domains\Documents\Models\DocumentRepositoryTemplateItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentTemplateService
{
    protected const DEFAULT_TEMPLATES = [
        [
            'name' => 'Training Program Template',
            'slug' => 'training-program-template',
            'owner_type' => \App\Domains\Programs\Models\Program::class,
            'description' => 'Program repository template for training delivery and reporting.',
            'items' => ['Concept Documents', 'Marketing', 'Facilitators', 'Attendance', 'Assessments', 'Reports', 'Certificates', 'Linked Assets'],
        ],
        [
            'name' => 'Infrastructure Project Template',
            'slug' => 'infrastructure-project-template',
            'owner_type' => \App\Domains\Projects\Models\Project::class,
            'description' => 'Project repository template for infrastructure delivery.',
            'items' => ['Design', 'Procurement', 'Contracts', 'Implementation', 'Testing', 'Handover', 'Reports', 'Linked Assets'],
        ],
        [
            'name' => 'Event Template',
            'slug' => 'event-template',
            'owner_type' => \App\Domains\Events\Models\Event::class,
            'description' => 'Event repository template for campaign and closure assets.',
            'items' => ['Posters', 'Sponsors', 'Registrations', 'Media', 'Reports', 'Working Files', 'Linked Assets'],
        ],
    ];

    public function ensureDefaults(?User $actor = null): void
    {
        foreach (self::DEFAULT_TEMPLATES as $template) {
            $model = DocumentRepositoryTemplate::query()->firstOrCreate(
                ['slug' => $template['slug']],
                [
                    'name' => $template['name'],
                    'owner_type' => $template['owner_type'],
                    'description' => $template['description'],
                    'is_system' => true,
                    'created_by' => $actor?->id,
                ]
            );

            if (! $model->allItems()->exists()) {
                foreach ($template['items'] as $index => $itemName) {
                    $model->allItems()->create([
                        'name' => $itemName,
                        'sort_order' => $index + 1,
                    ]);
                }
            }
        }
    }

    public function store(array $data, User $actor): DocumentRepositoryTemplate
    {
        return DB::transaction(function () use ($data, $actor) {
            $template = DocumentRepositoryTemplate::query()->create([
                'name' => trim((string) $data['name']),
                'slug' => Str::slug((string) $data['name']).'-'.Str::lower(Str::random(6)),
                'owner_type' => $data['owner_type'] ?? null,
                'description' => $data['description'] ?? null,
                'is_system' => false,
                'created_by' => $actor->id,
            ]);

            foreach (collect($data['items'] ?? [])->filter()->values() as $index => $name) {
                $template->allItems()->create([
                    'name' => trim((string) $name),
                    'sort_order' => $index + 1,
                ]);
            }

            return $template->fresh(['allItems']);
        });
    }

    public function apply(DocumentRepositoryTemplate $template, DocumentFolder $folder, ?User $actor = null): void
    {
        $items = $template->allItems()->whereNull('parent_item_id')->orderBy('sort_order')->get();

        foreach ($items as $item) {
            DocumentFolder::query()->firstOrCreate([
                'parent_id' => $folder->id,
                'owner_type' => $folder->owner_type,
                'owner_id' => $folder->owner_id,
                'folder_type' => DocumentFolder::TYPE_STANDARD,
                'name' => $item->name,
            ], [
                'created_by' => $actor?->id,
            ]);
        }
    }
}
