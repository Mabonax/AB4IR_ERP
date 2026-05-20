<?php

namespace App\Domains\Assets\Services;

use App\Domains\Assets\Models\Asset;
use App\Domains\Assets\Models\AssetAssignment;
use App\Domains\Assets\Models\AssetBatch;
use App\Domains\Assets\Models\AssetDecommissionRecord;
use App\Domains\Assets\Models\AssetMaintenanceRecord;
use App\Domains\Assets\Repositories\AssetRepositoryInterface;
use App\Domains\Projects\Models\Project;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\TaskManagement\Models\SupportTicket;
use App\Domains\TaskManagement\Services\SupportTicketService;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetService
{
    public function __construct(
        protected AssetRepositoryInterface $repository,
        protected SupportTicketService $supportTicketService
    ) {}

    public function paginateAssets(array $filters = []): LengthAwarePaginator
    {
        return $this->repository->paginate(15, $filters);
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
            if (($data['status'] ?? 'unassigned') === 'assigned') {
                throw ValidationException::withMessages([
                    'status' => ['Use the Assign action to set an asset as assigned.'],
                ]);
            }

            if (in_array(($data['status'] ?? 'unassigned'), ['maintenance', 'retired'], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Use the maintenance or decommission workflow instead of setting this status directly.'],
                ]);
            }

            $data = $this->normalizeSerialState($data);
            $asset = $this->repository->create($data);
            $asset->asset_code = $this->buildAssetCode($asset->id);
            $asset->save();

            return $asset;
        });
    }

    public function updateAsset(int $id, array $data): Asset
    {
        return DB::transaction(function () use ($id, $data) {
            $asset = $this->getAssetById($id);
            $data = $this->normalizeSerialState($data);

            if (($data['status'] ?? $asset->status) === 'assigned') {
                $active = AssetAssignment::query()
                    ->where('asset_id', $asset->id)
                    ->whereNull('returned_at')
                    ->exists();

                if (! $active) {
                    throw ValidationException::withMessages([
                        'status' => ['Use the Assign action to create assignment history before setting assigned status.'],
                    ]);
                }
            }

            if (
                isset($data['status'])
                && $data['status'] !== $asset->status
                && in_array($data['status'], ['maintenance', 'retired'], true)
            ) {
                throw ValidationException::withMessages([
                    'status' => ['Use the maintenance or decommission workflow instead of editing the status directly.'],
                ]);
            }

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

    public function createBatch(array $data): AssetBatch
    {
        return DB::transaction(function () use ($data) {
            $batch = AssetBatch::create([
                'name' => $data['name'],
                'asset_category_id' => (int) $data['asset_category_id'],
                'type' => $data['type'],
                'model_name' => $data['model_name'],
                'quantity' => (int) $data['quantity'],
                'serial_state' => $data['serial_state'],
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            for ($i = 1; $i <= (int) $data['quantity']; $i++) {
                $asset = $this->repository->create([
                    'asset_category_id' => (int) $data['asset_category_id'],
                    'asset_batch_id' => $batch->id,
                    'name' => $data['name'].' #'.$i,
                    'type' => $data['type'],
                    'model_name' => $data['model_name'],
                    'serial_state' => $data['serial_state'],
                    'serial_number' => null,
                    'status' => 'unassigned',
                    'staff_member_id' => null,
                ]);

                $asset->asset_code = $this->buildAssetCode($asset->id);
                $asset->save();
            }

            return $batch;
        });
    }

    public function updateBatch(int $id, array $data): AssetBatch
    {
        return DB::transaction(function () use ($id, $data) {
            $batch = AssetBatch::query()->findOrFail($id);

            $batch->update([
                'name' => $data['name'],
                'asset_category_id' => (int) $data['asset_category_id'],
                'type' => $data['type'],
                'model_name' => $data['model_name'],
                'serial_state' => $data['serial_state'],
                'notes' => $data['notes'] ?? null,
            ]);

            Asset::query()
                ->where('asset_batch_id', $batch->id)
                ->update([
                    'asset_category_id' => (int) $data['asset_category_id'],
                    'type' => $data['type'],
                    'model_name' => $data['model_name'],
                    'serial_state' => $data['serial_state'],
                ]);

            return $batch->fresh();
        });
    }

    public function deleteBatch(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $batch = AssetBatch::query()->findOrFail($id);

            Asset::query()
                ->where('asset_batch_id', $batch->id)
                ->update(['asset_batch_id' => null]);

            return $batch->delete();
        });
    }

    public function assignAsset(int $assetId, array $data): AssetAssignment
    {
        return DB::transaction(function () use ($assetId, $data) {
            $asset = $this->getAssetById($assetId);
            $mode = (string) $data['assignment_mode'];

            if ($asset->status === 'retired') {
                throw ValidationException::withMessages([
                    'assignment_mode' => ['Retired assets cannot be assigned.'],
                ]);
            }

            $departmentId = $data['department_id'] ? (int) $data['department_id'] : null;
            $staffId = $data['staff_member_id'] ? (int) $data['staff_member_id'] : null;
            $projectId = $data['project_id'] ? (int) $data['project_id'] : null;

            if ($mode === 'project') {
                if (! $projectId) {
                    throw ValidationException::withMessages([
                        'project_id' => ['Project is required for project assignment mode.'],
                    ]);
                }

                if ($departmentId || $staffId) {
                    throw ValidationException::withMessages([
                        'assignment_mode' => ['Project assignment must be exclusive.'],
                    ]);
                }

                $project = Project::find($projectId);
                if (! $project) {
                    throw ValidationException::withMessages([
                        'project_id' => ['Selected project does not exist.'],
                    ]);
                }
            } else {
                if (! $departmentId && ! $staffId) {
                    throw ValidationException::withMessages([
                        'department_id' => ['Provide a department and/or staff member for assignment.'],
                    ]);
                }

                if ($projectId) {
                    throw ValidationException::withMessages([
                        'assignment_mode' => ['Project cannot be set for department/staff assignment mode.'],
                    ]);
                }

                if ($staffId) {
                    $staff = StaffMember::find($staffId);
                    if (! $staff) {
                        throw ValidationException::withMessages([
                            'staff_member_id' => ['Selected staff member does not exist.'],
                        ]);
                    }

                    if (! $departmentId) {
                        $departmentId = $staff->department_id;
                    } elseif ((int) $staff->department_id !== (int) $departmentId) {
                        throw ValidationException::withMessages([
                            'staff_member_id' => ['Staff member must belong to selected department.'],
                        ]);
                    }
                }
            }

            $active = AssetAssignment::query()
                ->where('asset_id', $asset->id)
                ->whereNull('returned_at')
                ->latest('assigned_at')
                ->first();

            if ($mode === 'project' && $active && $active->staff_member_id) {
                $project = Project::find($projectId);

                if ($project && (int) $project->project_manager_id === (int) $active->staff_member_id) {
                    throw ValidationException::withMessages([
                        'project_id' => ['Asset is already assigned to this project manager; reassignment is not allowed.'],
                    ]);
                }
            }

            if ($active) {
                $active->update([
                    'returned_at' => now(),
                    'returned_by' => auth()->id(),
                    'notes' => trim(($active->notes ? $active->notes."\n" : '').'Auto-closed on reassignment'),
                ]);
            }

            $assignment = AssetAssignment::create([
                'asset_id' => $asset->id,
                'department_id' => $departmentId,
                'staff_member_id' => $staffId,
                'project_id' => $projectId,
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $asset->update([
                'staff_member_id' => $staffId,
                'status' => 'assigned',
            ]);

            return $assignment;
        });
    }

    public function returnAsset(int $assetId, ?string $notes = null): AssetAssignment
    {
        return DB::transaction(function () use ($assetId, $notes) {
            $asset = $this->getAssetById($assetId);

            $active = AssetAssignment::query()
                ->where('asset_id', $asset->id)
                ->whereNull('returned_at')
                ->latest('assigned_at')
                ->first();

            if (! $active) {
                throw ValidationException::withMessages([
                    'asset' => ['No active assignment found for this asset.'],
                ]);
            }

            $active->update([
                'returned_at' => now(),
                'returned_by' => auth()->id(),
                'notes' => trim(($active->notes ? $active->notes."\n" : '').($notes ?? 'Returned')),
            ]);

            $asset->update([
                'staff_member_id' => null,
                'status' => 'unassigned',
            ]);

            return $active->fresh(['department', 'staffMember', 'project']);
        });
    }

    public function startMaintenance(int $assetId, array $data, User $actor): AssetMaintenanceRecord
    {
        return DB::transaction(function () use ($assetId, $data, $actor) {
            $asset = $this->getAssetById($assetId);

            if ($asset->status === 'retired' || $asset->decommissionRecord) {
                throw ValidationException::withMessages([
                    'asset' => ['Decommissioned assets cannot be moved into maintenance.'],
                ]);
            }

            if ($asset->activeMaintenanceRecord) {
                throw ValidationException::withMessages([
                    'asset' => ['This asset is already in maintenance.'],
                ]);
            }

            $supportTicketId = isset($data['support_ticket_id']) && $data['support_ticket_id'] !== null && $data['support_ticket_id'] !== ''
                ? (int) $data['support_ticket_id']
                : null;

            if ($supportTicketId) {
                $ticket = SupportTicket::query()->find($supportTicketId);
                if (! $ticket) {
                    throw ValidationException::withMessages([
                        'support_ticket_id' => ['Selected support ticket does not exist.'],
                    ]);
                }

                if ((int) ($ticket->asset_id ?? 0) !== (int) $asset->id) {
                    throw ValidationException::withMessages([
                        'support_ticket_id' => ['Support ticket must belong to the selected asset.'],
                    ]);
                }
            }

            $this->closeActiveAssignmentForWorkflow($asset, 'Returned for maintenance');

            $asset->update([
                'staff_member_id' => null,
                'status' => 'maintenance',
            ]);

            return AssetMaintenanceRecord::query()->create([
                'asset_id' => $asset->id,
                'support_ticket_id' => $supportTicketId,
                'started_by_user_id' => $actor->id,
                'issue_summary' => $data['issue_summary'],
                'maintenance_notes' => $data['maintenance_notes'] ?? null,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        });
    }

    public function completeMaintenance(int $assetId, array $data, User $actor): AssetMaintenanceRecord
    {
        return DB::transaction(function () use ($assetId, $data, $actor) {
            $asset = $this->getAssetById($assetId);
            $record = $asset->activeMaintenanceRecord;

            if (! $record) {
                throw ValidationException::withMessages([
                    'asset' => ['No active maintenance record exists for this asset.'],
                ]);
            }

            $notes = trim(implode("\n", array_filter([
                $record->maintenance_notes,
                $data['completion_notes'] ?? null,
            ])));

            $record->update([
                'completed_by_user_id' => $actor->id,
                'maintenance_notes' => $notes !== '' ? $notes : null,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $asset->update([
                'status' => 'unassigned',
            ]);

            return $record->fresh(['supportTicket', 'startedBy', 'completedBy']);
        });
    }

    public function decommissionAsset(int $assetId, array $data, User $actor): AssetDecommissionRecord
    {
        return DB::transaction(function () use ($assetId, $data, $actor) {
            $asset = $this->getAssetById($assetId);

            if ($asset->decommissionRecord) {
                throw ValidationException::withMessages([
                    'asset' => ['This asset has already been decommissioned.'],
                ]);
            }

            if ($asset->activeMaintenanceRecord) {
                throw ValidationException::withMessages([
                    'asset' => ['Complete the maintenance workflow before decommissioning this asset.'],
                ]);
            }

            $this->closeActiveAssignmentForWorkflow($asset, 'Returned for decommissioning');

            $asset->update([
                'staff_member_id' => null,
                'status' => 'retired',
            ]);

            return AssetDecommissionRecord::query()->create([
                'asset_id' => $asset->id,
                'decommissioned_by_user_id' => $actor->id,
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'decommissioned_at' => now(),
            ]);
        });
    }

    public function reportFault(int $assetId, array $data, User $actor): SupportTicket
    {
        return DB::transaction(function () use ($assetId, $data, $actor) {
            $asset = $this->getAssetById($assetId);

            $this->assertCanReportFault($asset, $actor);

            $title = trim($data['title'] ?? '') !== ''
                ? $data['title']
                : 'Asset fault: '.($asset->asset_code ?: $asset->name);

            return $this->supportTicketService->createTicket([
                'title' => $title,
                'description' => $data['description'],
                'priority' => $data['priority'],
                'project_id' => $data['project_id'] ?? null,
                'program_id' => $data['program_id'] ?? null,
                'asset_id' => $asset->id,
            ], $actor);
        });
    }

    public function exportAssetsSpreadsheet(?int $categoryId = null): StreamedResponse
    {
        $category = null;
        $query = Asset::query()
            ->with([
                'category',
                'staffMember',
                'currentAssignment.department',
                'currentAssignment.staffMember',
                'currentAssignment.project',
                'activeMaintenanceRecord',
                'decommissionRecord',
            ])
            ->orderBy('asset_category_id')
            ->orderBy('model_name')
            ->orderBy('asset_code');

        if ($categoryId) {
            $category = \App\Domains\Assets\Models\AssetCategory::query()->findOrFail($categoryId);
            $query->where('asset_category_id', $categoryId);
        }

        $fileName = $category
            ? 'assets-'.$this->slugify($category->name).'-'.now()->format('Ymd-His').'.csv'
            : 'assets-register-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Category',
                'Asset Code',
                'Asset Name',
                'Type',
                'Model',
                'Serial State',
                'Serial Number',
                'Status',
                'Assigned To',
                'Maintenance Status',
                'Decommissioned',
            ]);

            $query->chunk(200, function ($assets) use ($handle) {
                foreach ($assets as $asset) {
                    fputcsv($handle, [
                        $asset->category?->name,
                        $asset->asset_code,
                        $asset->name,
                        $asset->type,
                        $asset->model_name,
                        $asset->serial_state,
                        $asset->serial_number,
                        $asset->status,
                        $this->formatAssignedTo($asset),
                        $asset->activeMaintenanceRecord ? 'In Maintenance' : ($asset->maintenanceRecords()->exists() ? 'Maintenance History' : 'None'),
                        $asset->decommissionRecord?->decommissioned_at?->toDateString(),
                    ]);
                }
            });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function managerDashboardData(): array
    {
        $user = auth()->user();
        $staff = $user?->staffMember;
        $departmentId = $staff?->department_id;

        if (! $departmentId) {
            return [
                'stats' => [
                    'portfolioAssets' => 0,
                    'departmentAssets' => 0,
                    'staffAssets' => 0,
                    'maintenanceAssets' => 0,
                    'retiredAssets' => 0,
                    'unreturnedAssets' => 0,
                    'recentActivities' => 0,
                ],
                'assetRows' => [],
                'assetsByStaff' => [],
                'activityRows' => [],
            ];
        }

        $scopedAssets = Asset::query()
            ->with([
                'category',
                'staffMember',
                'currentAssignment.department',
                'currentAssignment.staffMember',
                'currentAssignment.project',
                'maintenanceRecords',
                'activeMaintenanceRecord.supportTicket',
                'decommissionRecord.decommissionedBy',
            ])
            ->whereHas('assignments', function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId)
                    ->orWhereHas('staffMember', function ($staffQuery) use ($departmentId) {
                        $staffQuery->where('department_id', $departmentId);
                    });
            })
            ->latest('updated_at')
            ->get();

        $activeAssignments = AssetAssignment::query()
            ->with(['asset.category', 'staffMember', 'department', 'project', 'assignedBy', 'returnedBy'])
            ->whereNull('returned_at')
            ->where(function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId)
                    ->orWhereHas('staffMember', function ($staffQuery) use ($departmentId) {
                        $staffQuery->where('department_id', $departmentId);
                    });
            })
            ->latest('assigned_at')
            ->get();

        $assetRows = $scopedAssets->map(function (Asset $asset) {
            return [
                'asset_id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'asset_name' => $asset->name,
                'category_name' => $asset->category?->name,
                'status' => $asset->status,
                'assigned_to' => $this->formatAssignedTo($asset),
                'maintenance_state' => $asset->activeMaintenanceRecord
                    ? 'in_progress'
                    : ($asset->maintenanceRecords()->exists() ? 'history' : 'none'),
                'maintenance_issue' => $asset->activeMaintenanceRecord?->issue_summary,
                'decommissioned_at' => $asset->decommissionRecord?->decommissioned_at?->toDateTimeString(),
                'updated_at' => $asset->updated_at?->toDateTimeString(),
            ];
        })->values();

        $activityRows = AssetAssignment::query()
            ->with(['asset', 'staffMember', 'department', 'project', 'assignedBy', 'returnedBy'])
            ->where(function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId)
                    ->orWhereHas('staffMember', function ($staffQuery) use ($departmentId) {
                        $staffQuery->where('department_id', $departmentId);
                    });
            })
            ->latest('updated_at')
            ->limit(200)
            ->get()
            ->map(function (AssetAssignment $assignment) {
                $status = $assignment->returned_at ? 'returned' : 'assigned';
                $target = $assignment->project
                    ? 'Project: '.$assignment->project->name
                    : ($assignment->staffMember
                        ? 'Staff: '.trim($assignment->staffMember->first_name.' '.$assignment->staffMember->last_name)
                        : ($assignment->department?->name ? 'Department: '.$assignment->department->name : 'Unknown'));

                return [
                    'id' => $assignment->id,
                    'asset' => $assignment->asset?->name,
                    'asset_code' => $assignment->asset?->asset_code,
                    'status' => $status,
                    'target' => $target,
                    'assigned_by' => $assignment->assignedBy?->name,
                    'returned_by' => $assignment->returnedBy?->name,
                    'assigned_at' => $assignment->assigned_at?->toDateTimeString(),
                    'returned_at' => $assignment->returned_at?->toDateTimeString(),
                    'notes' => $assignment->notes,
                ];
            })->values();

        $assetsByStaff = $activeAssignments
            ->groupBy(fn (AssetAssignment $assignment) => $assignment->staff_member_id ?? 0)
            ->map(function ($items, $staffId) {
                $first = $items->first();
                $staffName = $first?->staffMember
                    ? trim($first->staffMember->first_name.' '.$first->staffMember->last_name)
                    : 'Department Pool';

                return [
                    'staff_member_id' => (int) $staffId,
                    'staff_name' => $staffName,
                    'assets_count' => $items->count(),
                    'assets' => $items->map(fn (AssetAssignment $assignment) => [
                        'asset_id' => $assignment->asset_id,
                        'asset_name' => $assignment->asset?->name,
                        'asset_code' => $assignment->asset?->asset_code,
                        'serial_number' => $assignment->asset?->serial_number,
                        'project_name' => $assignment->project?->name,
                        'assigned_at' => $assignment->assigned_at?->toDateTimeString(),
                    ])->values(),
                ];
            })->values();

        return [
            'stats' => [
                'portfolioAssets' => $scopedAssets->count(),
                'departmentAssets' => $activeAssignments->whereNotNull('department_id')->count(),
                'staffAssets' => $activeAssignments->whereNotNull('staff_member_id')->count(),
                'maintenanceAssets' => $scopedAssets->where('status', 'maintenance')->count(),
                'retiredAssets' => $scopedAssets->where('status', 'retired')->count(),
                'unreturnedAssets' => $activeAssignments->count(),
                'recentActivities' => $activityRows->count(),
            ],
            'assetRows' => $assetRows,
            'assetsByStaff' => $assetsByStaff,
            'activityRows' => $activityRows,
        ];
    }

    protected function buildAssetCode(int $id): string
    {
        return 'AST-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    protected function closeActiveAssignmentForWorkflow(Asset $asset, string $notes): void
    {
        $active = AssetAssignment::query()
            ->where('asset_id', $asset->id)
            ->whereNull('returned_at')
            ->latest('assigned_at')
            ->first();

        if (! $active) {
            return;
        }

        $active->update([
            'returned_at' => now(),
            'returned_by' => auth()->id(),
            'notes' => trim(($active->notes ? $active->notes."\n" : '').$notes),
        ]);
    }

    protected function assertCanReportFault(Asset $asset, User $actor): void
    {
        if ($actor->can('domain.assets.manage')) {
            return;
        }

        $staff = $actor->staffMember;
        $assignment = $asset->currentAssignment;

        $canReport = $staff && $assignment && (
            (int) ($assignment->staff_member_id ?? 0) === (int) $staff->id
            || (
                $assignment->department_id !== null
                && (int) $assignment->department_id === (int) ($staff->department_id ?? 0)
            )
        );

        if (! $canReport) {
            throw ValidationException::withMessages([
                'asset' => ['You can only report faults for assets currently assigned to you or your department.'],
            ]);
        }
    }

    protected function formatAssignedTo(Asset $asset): ?string
    {
        return $asset->currentAssignment?->project
            ? 'Project: '.$asset->currentAssignment->project->name
            : ($asset->currentAssignment?->staffMember
                ? 'Staff: '.trim($asset->currentAssignment->staffMember->first_name.' '.$asset->currentAssignment->staffMember->last_name)
                : ($asset->currentAssignment?->department
                    ? 'Department: '.$asset->currentAssignment->department->name
                    : null));
    }

    protected function normalizeSerialState(array $data): array
    {
        if (($data['serial_state'] ?? 'recorded') !== 'recorded') {
            $data['serial_number'] = null;
        }

        return $data;
    }

    protected function slugify(string $value): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?? 'assets', '-'));

        return $slug !== '' ? $slug : 'assets';
    }
}
