<?php

namespace App\Domains\Assets\Repositories;

use App\Domains\Assets\Models\AssetCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AssetCategoryRepository implements AssetCategoryRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return AssetCategory::latest()->paginate($perPage);
    }

    public function find(int $id): ?AssetCategory
    {
        return AssetCategory::find($id);
    }

    public function create(array $data): AssetCategory
    {
        return AssetCategory::create($data);
    }

    public function update(AssetCategory $category, array $data): AssetCategory
    {
        $category->update($data);
        return $category;
    }

    public function delete(AssetCategory $category): bool
    {
        return $category->delete();
    }
}
