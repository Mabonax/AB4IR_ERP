<?php

namespace App\Domains\BusinessDevelopment\Services;

use App\Domains\BusinessDevelopment\Models\BdsIncubatee;
use App\Domains\BusinessDevelopment\Repositories\BdsIncubateeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BdsIncubateeService
{
    public function __construct(
        protected BdsIncubateeRepositoryInterface $repository
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function getById(int $id): BdsIncubatee
    {
        $incubatee = $this->repository->find($id);

        if (! $incubatee) {
            throw new ModelNotFoundException('Incubatee not found.');
        }

        return $incubatee;
    }

    public function create(array $data): BdsIncubatee
    {
        $data['created_by'] = auth()->id();

        return $this->repository->create($data);
    }

    public function update(int $id, array $data): BdsIncubatee
    {
        $incubatee = $this->getById($id);
        $data['updated_by'] = auth()->id();

        return $this->repository->update($incubatee, $data);
    }

    public function delete(int $id): bool
    {
        $incubatee = $this->getById($id);

        return $this->repository->delete($incubatee);
    }
}

