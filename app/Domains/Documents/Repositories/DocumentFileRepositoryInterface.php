<?php

namespace App\Domains\Documents\Repositories;

use App\Domains\Documents\Models\DocumentFile;
use App\Domains\Documents\Models\DocumentFolder;
use Illuminate\Support\Collection;

interface DocumentFileRepositoryInterface
{
    public function find(int $id): ?DocumentFile;

    public function create(array $data): DocumentFile;

    public function update(DocumentFile $file, array $data): DocumentFile;

    public function delete(DocumentFile $file): bool;

    public function forFolder(DocumentFolder $folder): Collection;

    public function nextVersion(DocumentFolder $folder, string $title): int;
}
