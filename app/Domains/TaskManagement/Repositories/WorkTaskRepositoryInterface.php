<?php

namespace App\Domains\TaskManagement\Repositories;

use App\Domains\TaskManagement\Models\WorkTask;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface WorkTaskRepositoryInterface
{
    public function paginateVisible(Builder $query, int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): WorkTask;

    public function update(WorkTask $task, array $data): WorkTask;
}
