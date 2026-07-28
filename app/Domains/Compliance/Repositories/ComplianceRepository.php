<?php

namespace App\Domains\Compliance\Repositories;

use App\Domains\Compliance\Interfaces\ComplianceRepositoryInterface;
use App\Domains\Compliance\Models\ComplianceRecord;
use Illuminate\Support\Collection;

class ComplianceRepository implements ComplianceRepositoryInterface
{
    public function all(): Collection
    {
        return ComplianceRecord::query()
            ->with('organisation')
            ->orderByRaw('
                case
                    when due_date is null then 1
                    else 0
                end
            ')
            ->orderBy('due_date')
            ->orderBy('title')
            ->get();
    }

    public function find(int $id): ?ComplianceRecord
    {
        return ComplianceRecord::query()->with('organisation')->find($id);
    }

    public function create(array $data): ComplianceRecord
    {
        return ComplianceRecord::query()->create($data);
    }

    public function update(ComplianceRecord $record, array $data): ComplianceRecord
    {
        $record->update($data);

        return $record->fresh('organisation');
    }

    public function countAll(): int
    {
        return ComplianceRecord::query()->count();
    }

    public function countByStatus(string $status): int
    {
        return ComplianceRecord::query()->where('status', $status)->count();
    }

    public function countDueWithinDays(int $days): int
    {
        return ComplianceRecord::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', now()->toDateString())
            ->whereDate('due_date', '<=', now()->addDays($days)->toDateString())
            ->whereNotIn('status', ['submitted', 'approved'])
            ->count();
    }
}
