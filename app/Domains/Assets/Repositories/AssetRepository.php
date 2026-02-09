<?php

namespace App\Domains\Assets\Repositories;

use App\Domains\Assets\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AssetRepository implements AssetRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Asset::with(['category', 'staffMember'])
            ->latest()
            ->paginate($perPage);
    }

    public function all(): Collection
    {
        return Asset::with(['category', 'staffMember'])
            ->latest()
            ->get();
    }

    public function find(int $id): ?Asset
    {
        return Asset::with(['category', 'staffMember'])->find($id);
    }

    public function create(array $data): Asset
    {
        return Asset::create($data);
    }

    public function update(Asset $asset, array $data): Asset
    {
        $asset->update($data);
        return $asset;
    }

    public function delete(Asset $asset): bool
    {
        return $asset->delete();
    }
}
