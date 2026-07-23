<?php

namespace App\Domains\Documents\Repositories;

use App\Domains\Documents\Models\DocumentFile;
use App\Domains\Documents\Models\DocumentFolder;
use Illuminate\Support\Collection;

class DocumentFileRepository implements DocumentFileRepositoryInterface
{
    public function find(int $id): ?DocumentFile
    {
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
            ->find($id);
    }

    public function create(array $data): DocumentFile
    {
        return DocumentFile::query()->create($data);
    }

    public function update(DocumentFile $file, array $data): DocumentFile
    {
        $file->update($data);

        return $file->refresh();
    }

    public function delete(DocumentFile $file): bool
    {
        return $file->delete();
    }

    public function forFolder(DocumentFolder $folder): Collection
    {
        return $folder->files()->with([
            'uploader',
            'checkedOutBy',
            'versions.uploader',
            'approvals.approver',
            'links.linkable',
            'activityLogs.user',
        ])->get();
    }

    public function nextVersion(DocumentFolder $folder, string $title): int
    {
        return (int) DocumentFile::query()
            ->where('folder_id', $folder->id)
            ->where('title', $title)
            ->max('version') + 1;
    }
}
