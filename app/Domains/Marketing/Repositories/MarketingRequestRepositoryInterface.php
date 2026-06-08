<?php

namespace App\Domains\Marketing\Repositories;

use App\Domains\Marketing\Models\MarketingRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface MarketingRequestRepositoryInterface
{
    public function paginateVisible(Builder $query, int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): MarketingRequest;
}
