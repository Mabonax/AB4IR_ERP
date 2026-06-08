<?php

namespace App\Domains\Organization\Services;

use App\Domains\Marketing\Models\MarketingAsset;
use App\Domains\Marketing\Models\MarketingJob;
use App\Domains\Marketing\Models\MarketingJobDocument;
use App\Domains\Organization\Enums\OrganizationDocumentSlot;
use App\Domains\Organization\Models\OrganizationDocument;
use App\Domains\Organization\Notifications\OrganizationDocumentPublishedNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrganizationDocumentVaultService
{
    public function __construct(
        protected OrganizationProfileService $profileService,
    ) {}

    public function listForUser(User $user): Collection
    {
        $query = OrganizationDocument::query()
            ->with(['department:id,name', 'publishedBy:id,name', 'targetUsers:id,name'])
            ->latest();

        if ($user->can('create', OrganizationDocument::class)) {
            return $query->get();
        }

        $query = $this->accessibleQuery($user, $query);
        if (! $user->can('create', OrganizationDocument::class)) {
            $this->applyLifecycleVisibility($query);
        }

        return $query->get();
    }

    public function storeUpload(array $data, User $actor): OrganizationDocument
    {
        $file = $data['file'];

        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'file' => ['Upload a document file.'],
            ]);
        }

        return DB::transaction(function () use ($data, $actor, $file) {
            $this->validateAudience($data);
            $this->removeExistingInSlotIfNeeded($data);
            $stored = $file->storeAs(
                'organization/documents/'.trim((string) $data['document_type']),
                $this->generatedFileName($file->getClientOriginalName()),
                'public'
            );

            $document = OrganizationDocument::query()->create([
                'organization_profile_id' => $this->profileService->getProfile()->id,
                'title' => $data['title'],
                'document_type' => $data['document_type'],
                'description' => $data['description'] ?? null,
                'audience_scope' => $data['audience_scope'],
                'department_id' => $data['department_id'] ?? null,
                'slot_key' => $data['slot_key'] ?? null,
                'replace_existing' => (bool) ($data['replace_existing'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'effective_from' => filled($data['effective_from'] ?? null) ? Carbon::parse((string) $data['effective_from'])->startOfDay() : null,
                'effective_until' => filled($data['effective_until'] ?? null) ? Carbon::parse((string) $data['effective_until'])->endOfDay() : null,
                'disk' => 'public',
                'path' => $stored,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'published_by_user_id' => $actor->id,
            ]);

            $document->targetUsers()->sync($this->targetUserIds($data));
            $this->notifyRecipients($document, $actor);

            return $document->load(['department:id,name', 'publishedBy:id,name', 'targetUsers:id,name']);
        });
    }

    public function publishFromMarketingJob(MarketingJob $job, array $data, User $actor): OrganizationDocument
    {
        return DB::transaction(function () use ($job, $data, $actor) {
            if ($job->status !== 'approved') {
                throw ValidationException::withMessages([
                    'job' => ['Only approved marketing jobs can be published into the organization vault.'],
                ]);
            }

            [$disk, $path, $fileName, $mimeType, $fileSize, $sourceType, $sourceId] = match ($data['source_kind']) {
                'proof' => $this->marketingJobProofPayload($job),
                'document' => $this->marketingJobDocumentPayload($job, (int) $data['document_id']),
                default => throw ValidationException::withMessages([
                    'source_kind' => ['Choose the approved proof pack or an uploaded marketing document.'],
                ]),
            };

            return $this->persistPublishedDocument([
                'title' => $data['title'],
                'document_type' => $data['document_type'],
                'description' => $data['description'] ?? null,
                'audience_scope' => $data['audience_scope'],
                'department_id' => $data['department_id'] ?? null,
                'slot_key' => $data['slot_key'] ?? null,
                'replace_existing' => (bool) ($data['replace_existing'] ?? false),
                'selected_user_ids' => $data['selected_user_ids'] ?? [],
            ], $actor, $disk, $path, $fileName, $mimeType, $fileSize, $sourceType, $sourceId);
        });
    }

    public function publishFromMarketingAsset(MarketingAsset $asset, array $data, User $actor): OrganizationDocument
    {
        return DB::transaction(function () use ($asset, $data, $actor) {
            if (! $asset->asset_path || ! $asset->asset_disk) {
                throw ValidationException::withMessages([
                    'asset' => ['Only uploaded approved assets can be published into the organization vault.'],
                ]);
            }

            return $this->persistPublishedDocument([
                'title' => $data['title'],
                'document_type' => $data['document_type'],
                'description' => $data['description'] ?? null,
                'audience_scope' => $data['audience_scope'],
                'department_id' => $data['department_id'] ?? null,
                'slot_key' => $data['slot_key'] ?? null,
                'replace_existing' => (bool) ($data['replace_existing'] ?? false),
                'selected_user_ids' => $data['selected_user_ids'] ?? [],
            ], $actor, $asset->asset_disk, $asset->asset_path, $asset->asset_file_name ?? 'asset', $asset->asset_mime_type, $asset->asset_file_size, MarketingAsset::class, $asset->id);
        });
    }

    public function download(OrganizationDocument $document)
    {
        return Storage::disk($document->disk)->download($document->path, $document->file_name);
    }

    public function updateLifecycle(OrganizationDocument $document, string $action): OrganizationDocument
    {
        return match ($action) {
            'activate' => tap($document)->update([
                'is_active' => true,
                'effective_from' => $document->effective_from ?? now(),
            ]),
            'deactivate' => tap($document)->update([
                'is_active' => false,
            ]),
            'retire_now' => tap($document)->update([
                'is_active' => false,
                'effective_until' => now(),
            ]),
            default => throw ValidationException::withMessages([
                'action' => ['Choose a valid lifecycle action.'],
            ]),
        };
    }

    protected function persistPublishedDocument(
        array $data,
        User $actor,
        string $sourceDisk,
        string $sourcePath,
        string $sourceFileName,
        ?string $sourceMimeType,
        ?int $sourceFileSize,
        string $sourceType,
        int $sourceId,
    ): OrganizationDocument {
        $this->validateAudience($data);
        $this->removeExistingInSlotIfNeeded($data);

        if (! Storage::disk($sourceDisk)->exists($sourcePath)) {
            throw ValidationException::withMessages([
                'file' => ['The source file could not be found.'],
            ]);
        }

        $contents = Storage::disk($sourceDisk)->get($sourcePath);
        $storedPath = 'organization/documents/'.trim((string) $data['document_type']).'/'.$this->generatedFileName($sourceFileName);
        Storage::disk('public')->put($storedPath, $contents);

        $document = OrganizationDocument::query()->create([
            'organization_profile_id' => $this->profileService->getProfile()->id,
            'title' => $data['title'],
            'document_type' => $data['document_type'],
            'description' => $data['description'] ?? null,
            'audience_scope' => $data['audience_scope'],
            'department_id' => $data['department_id'] ?? null,
            'slot_key' => $data['slot_key'] ?? null,
            'replace_existing' => (bool) ($data['replace_existing'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'effective_from' => filled($data['effective_from'] ?? null) ? Carbon::parse((string) $data['effective_from'])->startOfDay() : null,
            'effective_until' => filled($data['effective_until'] ?? null) ? Carbon::parse((string) $data['effective_until'])->endOfDay() : null,
            'disk' => 'public',
            'path' => $storedPath,
            'file_name' => $sourceFileName,
            'mime_type' => $sourceMimeType,
            'file_size' => $sourceFileSize,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'published_by_user_id' => $actor->id,
        ]);

        $document->targetUsers()->sync($this->targetUserIds($data));
        $this->notifyRecipients($document, $actor);

        return $document->load(['department:id,name', 'publishedBy:id,name', 'targetUsers:id,name']);
    }

    protected function accessibleQuery(User $user, ?Builder $query = null): Builder
    {
        $query ??= OrganizationDocument::query();

        return $query->where(function (Builder $builder) use ($user) {
            $builder->where('audience_scope', 'all_staff')
                ->orWhere(function (Builder $department) use ($user) {
                    $department->where('audience_scope', 'department')
                        ->where('department_id', $user->staffMember?->department_id);
                })
                ->orWhere(function (Builder $selected) use ($user) {
                    $selected->where('audience_scope', 'selected_users')
                        ->whereHas('targetUsers', fn (Builder $query) => $query->whereKey($user->id));
                });
        });
    }

    protected function applyLifecycleVisibility(Builder $query): void
    {
        $now = now();

        $query->where('is_active', true)
            ->where(function (Builder $builder) use ($now) {
                $builder->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $now);
            })
            ->where(function (Builder $builder) use ($now) {
                $builder->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $now);
            });
    }

    protected function validateAudience(array $data): void
    {
        if (($data['audience_scope'] ?? null) === 'department' && empty($data['department_id'])) {
            throw ValidationException::withMessages([
                'department_id' => ['Select the department that should receive this document.'],
            ]);
        }

        if (($data['audience_scope'] ?? null) === 'selected_users' && $this->targetUserIds($data)->isEmpty()) {
            throw ValidationException::withMessages([
                'selected_user_ids' => ['Select at least one user for a targeted document.'],
            ]);
        }

        if (($data['replace_existing'] ?? false) && blank($data['slot_key'] ?? null)) {
            throw ValidationException::withMessages([
                'slot_key' => ['Provide a replacement slot key when replacing an existing organization document.'],
            ]);
        }

        if (filled($data['effective_from'] ?? null) && filled($data['effective_until'] ?? null)) {
            $effectiveFrom = Carbon::parse((string) $data['effective_from'])->startOfDay();
            $effectiveUntil = Carbon::parse((string) $data['effective_until'])->endOfDay();

            if ($effectiveUntil->lt($effectiveFrom)) {
                throw ValidationException::withMessages([
                    'effective_until' => ['The retirement date must be on or after the activation date.'],
                ]);
            }
        }

        if (filled($data['slot_key'] ?? null)) {
            $slot = OrganizationDocumentSlot::tryFrom((string) $data['slot_key']);

            if (! $slot || $slot->documentType()->value !== ($data['document_type'] ?? null)) {
                throw ValidationException::withMessages([
                    'slot_key' => ['Select a replacement slot that matches the chosen document type.'],
                ]);
            }
        }
    }

    protected function removeExistingInSlotIfNeeded(array $data): void
    {
        if (! ($data['replace_existing'] ?? false) || blank($data['slot_key'] ?? null)) {
            return;
        }

        $documents = OrganizationDocument::query()
            ->where('document_type', $data['document_type'])
            ->where('slot_key', $data['slot_key'])
            ->get();

        foreach ($documents as $document) {
            Storage::disk($document->disk)->delete($document->path);
            $document->delete();
        }
    }

    protected function targetUserIds(array $data): Collection
    {
        return collect($data['selected_user_ids'] ?? [])
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    protected function marketingJobProofPayload(MarketingJob $job): array
    {
        if (! $job->proof_path || ! $job->proof_disk) {
            throw ValidationException::withMessages([
                'source_kind' => ['This marketing job does not have an approved proof file to publish.'],
            ]);
        }

        return [
            $job->proof_disk,
            $job->proof_path,
            $job->proof_file_name ?? $job->title,
            $job->proof_mime_type,
            $job->proof_file_size,
            MarketingJob::class,
            $job->id,
        ];
    }

    protected function marketingJobDocumentPayload(MarketingJob $job, int $documentId): array
    {
        /** @var MarketingJobDocument|null $document */
        $document = $job->documents()->whereKey($documentId)->first();

        if (! $document) {
            throw ValidationException::withMessages([
                'document_id' => ['Select a valid marketing job document to publish.'],
            ]);
        }

        return [
            $document->disk,
            $document->path,
            $document->file_name,
            $document->mime_type,
            $document->file_size,
            MarketingJobDocument::class,
            $document->id,
        ];
    }

    protected function generatedFileName(string $originalFileName): string
    {
        $extension = pathinfo($originalFileName, PATHINFO_EXTENSION);

        return Str::uuid()->toString().($extension ? '.'.$extension : '');
    }

    protected function notifyRecipients(OrganizationDocument $document, User $actor): void
    {
        $context = sprintf('%s published "%s" to the organization document vault.', $actor->name, $document->title);

        $this->recipientQuery($document)
            ->whereKeyNot($actor->id)
            ->get()
            ->each(fn (User $user) => $user->notify(new OrganizationDocumentPublishedNotification($document, $context)));
    }

    protected function recipientQuery(OrganizationDocument $document): Builder
    {
        $query = User::query();

        return match ($document->audience_scope) {
            'department' => $query->whereHas('staffMember', fn (Builder $builder) => $builder->where('department_id', $document->department_id)),
            'selected_users' => $query->whereHas('organizationDocuments', fn (Builder $builder) => $builder->where('organization_documents.id', $document->id)),
            default => $query,
        };
    }
}
