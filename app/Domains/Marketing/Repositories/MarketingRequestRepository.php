<?php

namespace App\Domains\Marketing\Repositories;

use App\Domains\Marketing\Models\MarketingRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MarketingRequestRepository implements MarketingRequestRepositoryInterface
{
    public function paginateVisible(Builder $query, int $perPage = 15): LengthAwarePaginator
    {
        return $query->paginate($perPage)->withQueryString();
    }

    public function create(array $data): MarketingRequest
    {
        return MarketingRequest::query()->create($data);
    }
}
