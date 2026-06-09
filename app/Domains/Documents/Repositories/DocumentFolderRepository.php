<?php

namespace App\Domains\Documents\Repositories;

use App\Domains\Documents\Models\DocumentFolder;
use Illuminate\Support\Collection;

class DocumentFolderRepository implements DocumentFolderRepositoryInterface
{
    public function all(): Collection
    {
        return DocumentFolder::query()
            ->with(['children', 'files.uploader', 'parent'])
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): ?DocumentFolder
    {
        return DocumentFolder::query()
            ->with(['children', 'files.uploader', 'parent'])
            ->find($id);
    }

    public function create(array $data): DocumentFolder
    {
        return DocumentFolder::query()->create($data);
    }

    public function update(DocumentFolder $folder, array $data): DocumentFolder
    {
        $folder->update($data);

        return $folder->refresh();
    }

    public function delete(DocumentFolder $folder): bool
    {
        return $folder->delete();
    }
}
