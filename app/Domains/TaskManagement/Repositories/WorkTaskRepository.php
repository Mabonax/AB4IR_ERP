<?php

namespace App\Domains\TaskManagement\Repositories;

use App\Domains\TaskManagement\Models\WorkTask;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class WorkTaskRepository implements WorkTaskRepositoryInterface
{
    public function paginateVisible(Builder $query, int $perPage = 15): LengthAwarePaginator
    {
        return $query->paginate($perPage)->withQueryString();
    }

    public function create(array $data): WorkTask
    {
        return WorkTask::query()->create($data);
    }

    public function update(WorkTask $task, array $data): WorkTask
    {
        $task->update($data);

        return $task;
    }
}
