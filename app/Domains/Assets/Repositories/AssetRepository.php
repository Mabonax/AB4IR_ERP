<?php

namespace App\Domains\Assets\Repositories;

use App\Domains\Assets\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AssetRepository implements AssetRepositoryInterface
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return Asset::with([
            'category',
            'staffMember',
            'currentAssignment.department',
            'currentAssignment.staffMember',
            'currentAssignment.project',
            'activeMaintenanceRecord.supportTicket',
            'decommissionRecord',
            'supportTickets',
        ])
            ->when(! empty($filters['category_id']), function ($query) use ($filters) {
                $query->where('asset_category_id', (int) $filters['category_id']);
            })
            ->latest()
            ->paginate($perPage);
    }

    public function all(): Collection
    {
        return Asset::with([
            'category',
            'staffMember',
            'currentAssignment.department',
            'currentAssignment.staffMember',
            'currentAssignment.project',
        ])
            ->latest()
            ->get();
    }

    public function find(int $id): ?Asset
    {
        return Asset::with([
            'category',
            'staffMember',
            'batch',
            'currentAssignment.department',
            'currentAssignment.staffMember',
            'currentAssignment.project',
            'assignments.department',
            'assignments.staffMember',
            'assignments.project',
            'maintenanceRecords.supportTicket',
            'maintenanceRecords.startedBy',
            'maintenanceRecords.completedBy',
            'activeMaintenanceRecord.supportTicket',
            'decommissionRecord.decommissionedBy',
            'supportTickets.requester',
            'supportTickets.assignee',
        ])->find($id);
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
