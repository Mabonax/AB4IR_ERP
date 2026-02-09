<?php

namespace App\Domains\Facilitators\Services;

use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Facilitators\Repositories\FacilitatorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FacilitatorService
{
    public function __construct(
        protected FacilitatorRepositoryInterface $repository
    ) {}

    public function list(): Collection
    {
        return $this->repository->all();
    }

    public function paginateFacilitators(): LengthAwarePaginator
    {
        return $this->repository->paginate();
    }

    public function getById(int $id): Facilitator
    {
        $facilitator = $this->repository->find($id);

        if (! $facilitator) {
            throw new ModelNotFoundException('Facilitator not found.');
        }

        return $facilitator;
    }

    public function create(array $data): Facilitator
    {
        return DB::transaction(function () use ($data) {
            return $this->repository->create($data);
        });
    }

    public function update(int $id, array $data): Facilitator
    {
        return DB::transaction(function () use ($id, $data) {
            $facilitator = $this->getById($id);

            return $this->repository->update($facilitator, $data);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $facilitator = $this->getById($id);
            return $this->repository->delete($facilitator);
        });
    }
}
