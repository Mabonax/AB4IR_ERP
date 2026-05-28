<?php

namespace App\Domains\Marketing\Repositories;

use App\Domains\Marketing\Models\MarketingJob;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface MarketingJobRepositoryInterface
{
    public function paginateVisible(Builder $query, int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): MarketingJob;

    public function update(MarketingJob $job, array $data): MarketingJob;
}
