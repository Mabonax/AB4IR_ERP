<?php

namespace App\Domains\Documents\Repositories;

use App\Domains\Documents\Models\DocumentFolder;
use Illuminate\Support\Collection;

interface DocumentFolderRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?DocumentFolder;

    public function create(array $data): DocumentFolder;

    public function update(DocumentFolder $folder, array $data): DocumentFolder;

    public function delete(DocumentFolder $folder): bool;
}
