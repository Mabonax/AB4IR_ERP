<?php

namespace App\Domains\Projects\Repositories;

use App\Domains\Projects\Models\ProjectLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectLocationRepository implements ProjectLocationRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return ProjectLocation::with(['project', 'facilitator', 'province', 'enrollments.beneficiary'])
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id): ?ProjectLocation
    {
        return ProjectLocation::with(['project', 'facilitator', 'province', 'enrollments.beneficiary'])->find($id);
    }

    public function create(array $data): ProjectLocation
    {
        return ProjectLocation::create($data);
    }

    public function update(ProjectLocation $location, array $data): ProjectLocation
    {
        $location->update($data);

        return $location;
    }

    public function delete(ProjectLocation $location): bool
    {
        return $location->delete();
    }
}
