<?php

namespace App\Domains\Resolutions\Repositories;

use App\Domains\Resolutions\Interfaces\ResolutionRepositoryInterface;
use App\Domains\Resolutions\Models\Resolution;
use Illuminate\Support\Collection;

class ResolutionRepository implements ResolutionRepositoryInterface
{
    public function all(): Collection
    {
        return Resolution::query()
            ->with(['organisation', 'meeting', 'owner'])
            ->orderByRaw('case when due_date is null then 1 else 0 end')
            ->orderBy('due_date')
            ->get();
    }

    public function find(int $id): ?Resolution
    {
        return Resolution::query()->find($id);
    }

    public function create(array $data): Resolution
    {
        return Resolution::query()->create($data);
    }

    public function update(Resolution $resolution, array $data): Resolution
    {
        $resolution->update($data);

        return $resolution->refresh();
    }

    public function countByStatus(string $status): int
    {
        return Resolution::query()->where('status', $status)->count();
    }
}
