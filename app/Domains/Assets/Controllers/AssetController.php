<?php

namespace App\Domains\Assets\Controllers;

use App\Domains\Assets\Models\AssetBatch;
use App\Domains\Assets\Models\AssetCategory;
use App\Domains\Assets\Requests\AssignAssetRequest;
use App\Domains\Assets\Requests\ReturnAssetRequest;
use App\Domains\Assets\Requests\StoreAssetBatchRequest;
use App\Domains\Assets\Requests\StoreAssetRequest;
use App\Domains\Assets\Requests\UpdateAssetBatchRequest;
use App\Domains\Assets\Requests\UpdateAssetRequest;
use App\Domains\Assets\Resources\AssetResource;
use App\Domains\Assets\Services\AssetService;
use App\Domains\Assets\Models\Asset;
use App\Domains\Projects\Models\Project;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssetController extends Controller
{
    public function __construct(
        protected AssetService $service
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'category_id' => $request->input('category_id'),
        ];

        $assets = $this->service->paginateAssets($filters);

        $categories = AssetCategory::select('id', 'name')->orderBy('name')->get();
        $departments = StaffDepartment::select('id', 'name')->orderBy('name')->get();
        $staffMembers = StaffMember::select('id', 'first_name', 'last_name')
            ->with('department:id,name')
            ->orderBy('first_name')
            ->get()
            ->map(fn ($staff) => [
                'id' => $staff->id,
                'name' => trim($staff->first_name.' '.$staff->last_name),
                'department_id' => $staff->department_id,
                'department_name' => $staff->department?->name,
            ]);
        $projects = Project::select('id', 'name', 'project_manager_id')->orderBy('name')->get();
        $batches = AssetBatch::with('category:id,name')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (AssetBatch $batch) => [
                'id' => $batch->id,
                'name' => $batch->name,
                'category_name' => $batch->category?->name,
                'type' => $batch->type,
                'model_name' => $batch->model_name,
                'quantity' => $batch->quantity,
                'serial_state' => $batch->serial_state,
                'notes' => $batch->notes,
                'created_at' => $batch->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Assets/Index', [
            'assets' => AssetResource::collection($assets),
            'categories' => $categories,
            'departments' => $departments,
            'staffMembers' => $staffMembers,
            'projects' => $projects,
            'batches' => $batches,
            'filters' => $filters,
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
                'pendingSerialAssets' => Asset::where('serial_state', 'pending')->count(),
                'noSerialAssets' => Asset::where('serial_state', 'no_serial')->count(),
                'totalBatches' => AssetBatch::count(),
            ],
        ]);
    }

    public function managerDashboard()
    {
        $data = $this->service->managerDashboardData();

        return Inertia::render('Assets/ManagerDashboard', $data);
    }

    public function registerCategories()
    {
        $categories = AssetCategory::query()
            ->withCount([
                'assets as active_assets_count' => function ($query) {
                    $query->where('status', '!=', 'retired');
                },
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (AssetCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'active_assets_count' => (int) $category->active_assets_count,
            ]);

        return Inertia::render('Assets/RegisterCategories', [
            'categories' => $categories,
        ]);
    }

    public function registerModels(int $category)
    {
        $categoryModel = AssetCategory::query()->select('id', 'name')->findOrFail($category);

        $modelCounts = Asset::query()
            ->where('asset_category_id', $category)
            ->where('status', '!=', 'retired')
            ->pluck('model_name')
            ->map(function (?string $modelName): string {
                $trimmed = trim((string) $modelName);

                return $trimmed === '' ? 'Unspecified' : $trimmed;
            })
            ->countBy()
            ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE);

        $models = $modelCounts
            ->map(fn (int $count, string $modelName) => [
                'model_name' => $modelName,
                'active_assets_count' => $count,
                'model_key' => rawurlencode($modelName),
            ])
            ->values();

        return Inertia::render('Assets/RegisterModels', [
            'category' => $categoryModel,
            'models' => $models,
        ]);
    }

    public function registerItems(int $category, string $model)
    {
        $categoryModel = AssetCategory::query()->select('id', 'name')->findOrFail($category);
        $modelName = urldecode($model);

        $items = Asset::query()
            ->with([
                'currentAssignment.department',
                'currentAssignment.staffMember',
                'currentAssignment.project',
                'assignments',
            ])
            ->where('asset_category_id', $category)
            ->where('status', '!=', 'retired')
            ->where(function ($query) use ($modelName) {
                if ($modelName === 'Unspecified') {
                    $query->whereNull('model_name')->orWhereRaw("TRIM(model_name) = ''");
                    return;
                }

                $query->where('model_name', $modelName);
            })
            ->orderBy('asset_code')
            ->get()
            ->map(function (Asset $asset) {
                $assignedTo = $asset->currentAssignment?->project
                    ? 'Project: '.$asset->currentAssignment->project->name
                    : ($asset->currentAssignment?->staffMember
                        ? 'Staff: '.trim($asset->currentAssignment->staffMember->first_name.' '.$asset->currentAssignment->staffMember->last_name)
                        : ($asset->currentAssignment?->department
                            ? 'Department: '.$asset->currentAssignment->department->name
                            : null));

                return [
                    'id' => $asset->id,
                    'asset_code' => $asset->asset_code,
                    'serial_number' => $asset->serial_number,
                    'serial_state' => $asset->serial_state,
                    'status' => $asset->status,
                    'assigned_to' => $assignedTo,
                    'last_assignment_at' => $asset->assignments->max('assigned_at')?->toDateTimeString(),
                ];
            })->values();

        return Inertia::render('Assets/RegisterItems', [
            'category' => $categoryModel,
            'model_name' => $modelName,
            'items' => $items,
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

    public function storeBatch(StoreAssetBatchRequest $request)
    {
        $batch = $this->service->createBatch($request->validated());

        return redirect()->back()->with('success', "Asset batch created ({$batch->quantity} items)");
    }

    public function updateBatch(UpdateAssetBatchRequest $request, int $batch)
    {
        $this->service->updateBatch($batch, $request->validated());

        return redirect()->back()->with('success', 'Asset batch updated');
    }

    public function destroyBatch(int $batch)
    {
        $this->service->deleteBatch($batch);

        return redirect()->back()->with('success', 'Asset batch deleted');
    }

    public function assign(AssignAssetRequest $request, int $asset)
    {
        $this->service->assignAsset($asset, $request->validated());

        return redirect()->back()->with('success', 'Asset assigned');
    }

    public function returnAsset(ReturnAssetRequest $request, int $asset)
    {
        $this->service->returnAsset($asset, $request->validated()['notes'] ?? null);

        return redirect()->back()->with('success', 'Asset returned');
    }
}
