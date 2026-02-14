<?php

namespace App\Domains\Assets\Repositories;

use App\Domains\Assets\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AssetRepositoryInterface
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function all(): Collection;

    public function find(int $id): ?Asset;

    public function create(array $data): Asset;

    public function update(Asset $asset, array $data): Asset;

    public function delete(Asset $asset): bool;
}
