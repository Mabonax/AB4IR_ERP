<?php

namespace App\Domains\Assets\Repositories;

use App\Domains\Assets\Models\AssetCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AssetCategoryRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?AssetCategory;

    public function create(array $data): AssetCategory;

    public function update(AssetCategory $category, array $data): AssetCategory;

    public function delete(AssetCategory $category): bool;
}
