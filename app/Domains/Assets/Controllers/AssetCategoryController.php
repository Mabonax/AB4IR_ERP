<?php

namespace App\Domains\Assets\Controllers;

use App\Domains\Assets\Requests\StoreAssetCategoryRequest;
use App\Domains\Assets\Requests\UpdateAssetCategoryRequest;
use App\Domains\Assets\Resources\AssetCategoryResource;
use App\Domains\Assets\Services\AssetCategoryService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class AssetCategoryController extends Controller
{
    public function __construct(
        protected AssetCategoryService $service
    ) {}

    public function index()
    {
        $categories = $this->service->paginateCategories();

        return Inertia::render('AssetCategories/Index', [
            'categories' => AssetCategoryResource::collection($categories),
        ]);
    }

    public function store(StoreAssetCategoryRequest $request)
    {
        $this->service->createCategory($request->validated());

        return redirect()->back()->with('success', 'Asset category created');
    }

    public function show(int $asset_category)
    {
        $model = $this->service->getCategoryById($asset_category);

        return response()->json(new AssetCategoryResource($model));
    }

    public function update(UpdateAssetCategoryRequest $request, int $asset_category)
    {
        $this->service->updateCategory($asset_category, $request->validated());

        return redirect()->back()->with('success', 'Asset category updated');
    }

    public function destroy(int $asset_category)
    {
        $this->service->deleteCategory($asset_category);

        return redirect()->back()->with('success', 'Asset category deleted');
    }
}
