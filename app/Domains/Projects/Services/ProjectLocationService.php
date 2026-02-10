<?php

namespace App\Domains\Projects\Services;

use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Projects\Repositories\ProjectLocationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class ProjectLocationService
{
    public function __construct(
        protected ProjectLocationRepositoryInterface $repository
    ) {}

    public function paginateLocations(): LengthAwarePaginator
    {
        return $this->repository->paginate();
    }

    public function getLocationById(int $id): ProjectLocation
    {
        $location = $this->repository->find($id);

        if (! $location) {
            throw new ModelNotFoundException('Project location not found.');
        }

        return $location;
    }

    public function createLocation(array $data): ProjectLocation
    {
        return DB::transaction(function () use ($data) {
            return $this->repository->create($data);
        });
    }

    public function updateLocation(int $id, array $data): ProjectLocation
    {
        return DB::transaction(function () use ($id, $data) {
            $location = $this->getLocationById($id);
            return $this->repository->update($location, $data);
        });
    }

    public function deleteLocation(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $location = $this->getLocationById($id);
            return $this->repository->delete($location);
        });
    }
}
