<?php

namespace App\Domains\Compliance\Services;

use App\Domains\Compliance\Interfaces\ComplianceRepositoryInterface;
use App\Domains\Compliance\Models\ComplianceRecord;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class ComplianceService
{
    public function __construct(
        protected ComplianceRepositoryInterface $repository
    ) {}

    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function findOrFail(int $id): ComplianceRecord
    {
        $record = $this->repository->find($id);

        if (! $record) {
            throw new ModelNotFoundException('Compliance record not found.');
        }

        return $record;
    }

    public function create(array $data): ComplianceRecord
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ComplianceRecord
    {
        $record = $this->findOrFail($id);

        return $this->repository->update($record, $data);
    }

    public function registry(): array
    {
        $records = $this->repository->all();

        return [
            'stats' => $this->dashboardWidget(),
            'records' => $records->map(function (ComplianceRecord $record) {
                return [
                    'id' => $record->id,
                    'organisation_id' => $record->organisation_id,
                    'organisation_name' => $record->organisation?->name,
                    'title' => $record->title,
                    'compliance_area' => $record->compliance_area,
                    'reference_code' => $record->reference_code,
                    'filing_frequency' => $record->filing_frequency,
                    'due_date' => $record->due_date?->toDateString(),
                    'submitted_at' => $record->submitted_at?->toDateString(),
                    'status' => $record->status,
                    'owner_name' => $record->owner_name,
                    'notes' => $record->notes,
                ];
            })->values()->all(),
        ];
    }

    public function dashboardWidget(): array
    {
        $overdue = ComplianceRecord::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['submitted', 'approved'])
            ->count();

        return [
            'total' => $this->repository->countAll(),
            'submitted' => $this->repository->countByStatus('submitted'),
            'overdue' => $overdue,
            'due_soon' => $this->repository->countDueWithinDays(30),
        ];
    }
}
