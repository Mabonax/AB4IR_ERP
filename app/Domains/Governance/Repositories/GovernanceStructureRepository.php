<?php

namespace App\Domains\Governance\Repositories;

use App\Domains\Governance\Interfaces\GovernanceStructureRepositoryInterface;
use App\Domains\Governance\Models\GovernanceStructure;
use Illuminate\Support\Collection;

class GovernanceStructureRepository implements GovernanceStructureRepositoryInterface
{
    public function all(): Collection
    {
        return GovernanceStructure::query()
            ->with('organisation')
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): ?GovernanceStructure
    {
        return GovernanceStructure::query()->find($id);
    }

    public function create(array $data): GovernanceStructure
    {
        return GovernanceStructure::query()->create($data);
    }

    public function update(GovernanceStructure $structure, array $data): GovernanceStructure
    {
        $structure->update($data);

        return $structure->refresh();
    }

    public function countByStatus(string $status): int
    {
        return GovernanceStructure::query()->where('status', $status)->count();
    }
}
