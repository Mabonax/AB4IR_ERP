<?php

namespace App\Domains\Documents\Services;

use App\Domains\Documents\Models\DocumentFile;
use App\Models\User;
use Illuminate\Support\Collection;

class DocumentSearchService
{
    public function __construct(
        protected DocumentAccessService $accessService,
    ) {}

    public function search(User $user, ?string $term = null, ?string $status = null): Collection
    {
        $term = trim((string) $term);

        if ($term === '' && blank($status)) {
            return collect();
        }

        return DocumentFile::query()
            ->with([
                'folder.parent',
                'uploader',
                'checkedOutBy',
                'versions.uploader',
                'approvals.approver',
                'links.linkable',
                'activityLogs.user',
            ])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($builder) use ($term) {
                    $builder
                        ->where('title', 'like', '%'.$term.'%')
                        ->orWhere('description', 'like', '%'.$term.'%')
                        ->orWhere('original_name', 'like', '%'.$term.'%')
                        ->orWhereHas('uploader', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$term.'%'))
                        ->orWhereHas('links', fn ($linkQuery) => $linkQuery->where('relationship_type', 'like', '%'.$term.'%'));
                });
            })
            ->latest('updated_at')
            ->get()
            ->filter(fn (DocumentFile $file) => $this->accessService->canViewFile($user, $file))
            ->filter(function (DocumentFile $file) use ($term) {
                if ($term === '') {
                    return true;
                }

                $needle = mb_strtolower($term);
                $linkText = $file->links->map(function ($link) {
                    $label = $link->linkable?->title
                        ?? $link->linkable?->name
                        ?? $link->linkable?->organization_name
                        ?? $link->linkable?->asset_file_name
                        ?? ($link->linkable && isset($link->linkable->first_name)
                            ? trim($link->linkable->first_name.' '.$link->linkable->last_name)
                            : '');

                    return mb_strtolower(trim($label.' '.$link->relationship_type));
                })->implode(' ');

                return str_contains(mb_strtolower($file->title), $needle)
                    || str_contains(mb_strtolower((string) $file->description), $needle)
                    || str_contains(mb_strtolower($file->original_name), $needle)
                    || str_contains(mb_strtolower((string) $file->uploader?->name), $needle)
                    || str_contains($linkText, $needle);
            })
            ->values();
    }
}
