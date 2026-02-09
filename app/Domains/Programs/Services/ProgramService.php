<?php

namespace App\Domains\Programs\Services;

use App\Domains\Programs\Models\Program;
use App\Domains\Programs\Repositories\ProgramRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProgramService
{
    public function __construct(
        protected ProgramRepositoryInterface $repository
    ) {}

    public function list(): Collection
    {
        return $this->repository->all();
    }

    public function paginatePrograms(): LengthAwarePaginator
    {
        return $this->repository->paginate();
    }

    public function getById(int $id): Program
    {
        $program = $this->repository->find($id);

        if (! $program) {
            throw new ModelNotFoundException('Program not found.');
        }

        return $program;
    }

    public function create(array $data): Program
    {
        return DB::transaction(function () use ($data) {
            return $this->repository->create($data);
        });
    }

    public function update(int $id, array $data): Program
    {
        return DB::transaction(function () use ($id, $data) {
            $program = $this->getById($id);

            return $this->repository->update($program, $data);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $program = $this->getById($id);
            return $this->repository->delete($program);
        });
    }
}