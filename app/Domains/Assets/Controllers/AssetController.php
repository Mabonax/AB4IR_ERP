<?php

namespace App\Domains\Assets\Controllers;

use App\Domains\Assets\Models\AssetCategory;
use App\Domains\Assets\Requests\StoreAssetRequest;
use App\Domains\Assets\Requests\UpdateAssetRequest;
use App\Domains\Assets\Resources\AssetResource;
use App\Domains\Assets\Services\AssetService;
use App\Domains\Assets\Models\Asset;
use App\Domains\Staff\Models\StaffMember;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class AssetController extends Controller
{
    public function __construct(
        protected AssetService $service
    ) {}

    public function index()
    {
        $assets = $this->service->paginateAssets();

        $categories = AssetCategory::select('id', 'name')->orderBy('name')->get();
        $staffMembers = StaffMember::select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn ($staff) => [
                'id' => $staff->id,
                'name' => trim($staff->first_name.' '.$staff->last_name),
            ]);

        return Inertia::render('Assets/Index', [
            'assets' => AssetResource::collection($assets),
            'categories' => $categories,
            'staffMembers' => $staffMembers,
        ]);
    }

    public function dashboard()
    {
        return Inertia::render('Assets/Dashboard', [
            'stats' => [
                'totalAssets' => Asset::count(),
                'assignedAssets' => Asset::where('status', 'assigned')->count(),
                'unassignedAssets' => Asset::where('status', 'unassigned')->count(),
                'maintenanceAssets' => Asset::where('status', 'maintenance')->count(),
                'retiredAssets' => Asset::where('status', 'retired')->count(),
            ],
        ]);
    }

    public function store(StoreAssetRequest $request)
    {
        $this->service->createAsset($request->validated());

        return redirect()->back()->with('success', 'Asset created');
    }

    public function show(int $asset)
    {
        $model = $this->service->getAssetById($asset);

        return response()->json(new AssetResource($model));
    }

    public function update(UpdateAssetRequest $request, int $asset)
    {
        $this->service->updateAsset($asset, $request->validated());

        return redirect()->back()->with('success', 'Asset updated');
    }

    public function destroy(int $asset)
    {
        $this->service->deleteAsset($asset);

        return redirect()->back()->with('success', 'Asset deleted');
    }
}
