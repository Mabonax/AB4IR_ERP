<?php

namespace App\Domains\Assets\Services;

use App\Domains\Assets\Models\AssetCategory;
use App\Domains\Assets\Repositories\AssetCategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class AssetCategoryService
{
    public function __construct(
        protected AssetCategoryRepositoryInterface $repository
    ) {}

    public function paginateCategories(): LengthAwarePaginator
    {
        return $this->repository->paginate();
    }

    public function getCategoryById(int $id): AssetCategory
    {
        $category = $this->repository->find($id);

        if (! $category) {
            throw new ModelNotFoundException('Asset category not found.');
        }

        return $category;
    }

    public function createCategory(array $data): AssetCategory
    {
        return DB::transaction(function () use ($data) {
            return $this->repository->create($data);
        });
    }

    public function updateCategory(int $id, array $data): AssetCategory
    {
        return DB::transaction(function () use ($id, $data) {
            $category = $this->getCategoryById($id);

            return $this->repository->update($category, $data);
        });
    }

    public function deleteCategory(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $category = $this->getCategoryById($id);

            return $this->repository->delete($category);
        });
    }
}
