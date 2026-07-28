<?php

namespace App\Domains\Governance\Interfaces;

use App\Domains\Governance\Models\GovernanceStructure;
use Illuminate\Support\Collection;

interface GovernanceStructureRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?GovernanceStructure;

    public function create(array $data): GovernanceStructure;

    public function update(GovernanceStructure $structure, array $data): GovernanceStructure;

    public function countByStatus(string $status): int;
}
