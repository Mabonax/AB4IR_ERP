<?php

namespace App\Domains\Compliance\Interfaces;

use App\Domains\Compliance\Models\ComplianceRecord;
use Illuminate\Support\Collection;

interface ComplianceRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?ComplianceRecord;

    public function create(array $data): ComplianceRecord;

    public function update(ComplianceRecord $record, array $data): ComplianceRecord;

    public function countAll(): int;

    public function countByStatus(string $status): int;

    public function countDueWithinDays(int $days): int;
}
