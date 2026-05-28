<?php

namespace App\Domains\Marketing\Repositories;

use App\Domains\Marketing\Models\MarketingJob;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MarketingJobRepository implements MarketingJobRepositoryInterface
{
    public function paginateVisible(Builder $query, int $perPage = 15): LengthAwarePaginator
    {
        return $query->paginate($perPage)->withQueryString();
    }

    public function create(array $data): MarketingJob
    {
        return MarketingJob::query()->create($data);
    }

    public function update(MarketingJob $job, array $data): MarketingJob
    {
        $job->update($data);

        return $job;
    }
}
