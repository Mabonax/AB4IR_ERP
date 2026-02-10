<?php

namespace App\Domains\Projects\Repositories;

use App\Domains\Projects\Models\ProjectLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProjectLocationRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?ProjectLocation;

    public function create(array $data): ProjectLocation;

    public function update(ProjectLocation $location, array $data): ProjectLocation;

    public function delete(ProjectLocation $location): bool;
}
