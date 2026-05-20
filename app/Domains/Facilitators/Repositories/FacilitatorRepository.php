<?php

namespace App\Domains\Facilitators\Repositories;

use App\Domains\Facilitators\Models\Facilitator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class FacilitatorRepository implements FacilitatorRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Facilitator::with(['province', 'user'])->latest()->paginate($perPage);
    }

    public function all(): Collection
    {
        return Facilitator::with(['province', 'user'])->latest()->get();
    }

    public function find(int $id): ?Facilitator
    {
        return Facilitator::with(['province', 'user'])->find($id);
    }

    public function create(array $data): Facilitator
    {
        return Facilitator::create($data);
    }

    public function update(Facilitator $facilitator, array $data): Facilitator
    {
        $facilitator->update($data);

        return $facilitator;
    }

    public function delete(Facilitator $facilitator): bool
    {
        return $facilitator->delete();
    }
}
