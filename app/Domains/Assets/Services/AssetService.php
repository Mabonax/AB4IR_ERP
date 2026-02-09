<?php

namespace App\Domains\Assets\Services;

use App\Domains\Assets\Models\Asset;
use App\Domains\Assets\Repositories\AssetRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class AssetService
{
    public function __construct(
        protected AssetRepositoryInterface $repository
    ) {}

    public function paginateAssets(): LengthAwarePaginator
    {
        return $this->repository->paginate();
    }

    public function getAssetById(int $id): Asset
    {
        $asset = $this->repository->find($id);

        if (! $asset) {
            throw new ModelNotFoundException('Asset not found.');
        }

        return $asset;
    }

    public function createAsset(array $data): Asset
    {
        return DB::transaction(function () use ($data) {
            return $this->repository->create($data);
        });
    }

    public function updateAsset(int $id, array $data): Asset
    {
        return DB::transaction(function () use ($id, $data) {
            $asset = $this->getAssetById($id);
            return $this->repository->update($asset, $data);
        });
    }

    public function deleteAsset(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $asset = $this->getAssetById($id);
            return $this->repository->delete($asset);
        });
    }
}
