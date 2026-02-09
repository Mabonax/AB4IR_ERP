<?php

namespace App\Domains\Facilitators\Repositories;

use App\Domains\Facilitators\Models\Facilitator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface FacilitatorRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function all(): Collection;

    public function find(int $id): ?Facilitator;

    public function create(array $data): Facilitator;

    public function update(Facilitator $facilitator, array $data): Facilitator;

    public function delete(Facilitator $facilitator): bool;
}
