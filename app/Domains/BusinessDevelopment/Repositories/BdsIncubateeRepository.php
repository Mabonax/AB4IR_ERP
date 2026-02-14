<?php

namespace App\Domains\BusinessDevelopment\Repositories;

use App\Domains\BusinessDevelopment\Models\BdsIncubatee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BdsIncubateeRepository implements BdsIncubateeRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return BdsIncubatee::with('province')->latest()->paginate($perPage);
    }

    public function find(int $id): ?BdsIncubatee
    {
        return BdsIncubatee::with('province')->find($id);
    }

    public function create(array $data): BdsIncubatee
    {
        return BdsIncubatee::create($data);
    }

    public function update(BdsIncubatee $incubatee, array $data): BdsIncubatee
    {
        $incubatee->update($data);

        return $incubatee;
    }

    public function delete(BdsIncubatee $incubatee): bool
    {
        return $incubatee->delete();
    }
}

