<?php

namespace App\Domains\BusinessDevelopment\Repositories;

use App\Domains\BusinessDevelopment\Models\BdsIncubatee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BdsIncubateeRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?BdsIncubatee;

    public function create(array $data): BdsIncubatee;

    public function update(BdsIncubatee $incubatee, array $data): BdsIncubatee;

    public function delete(BdsIncubatee $incubatee): bool;
}

