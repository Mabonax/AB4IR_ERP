<?php

namespace App\Domains\Committees\Repositories;

use App\Domains\Committees\Interfaces\CommitteeRepositoryInterface;
use App\Domains\Committees\Models\Committee;
use Illuminate\Support\Collection;

class CommitteeRepository implements CommitteeRepositoryInterface
{
    public function all(): Collection
    {
        return Committee::query()
            ->with(['organisation', 'chairperson', 'secretary', 'members.user', 'meetings'])
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): ?Committee
    {
        return Committee::query()->find($id);
    }

    public function create(array $data): Committee
    {
        return Committee::query()->create($data);
    }

    public function update(Committee $committee, array $data): Committee
    {
        $committee->update($data);

        return $committee->refresh();
    }

    public function countByStatus(string $status): int
    {
        return Committee::query()->where('status', $status)->count();
    }
}
