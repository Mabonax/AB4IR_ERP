<?php

namespace App\Domains\Programs\Repositories;

use App\Domains\Programs\Models\Program;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProgramRepository implements ProgramRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Program::latest()->paginate($perPage);
    }

    public function all(): Collection
    {
        return Program::latest()->get();
    }

    public function find(int $id): ?Program
    {
        return Program::find($id);
    }

    public function create(array $data): Program
    {
        return Program::create($data);
    }

    public function update(Program $program, array $data): Program
    {
        $program->update($data);
        return $program;
    }

    public function delete(Program $program): bool
    {
        return $program->delete();
    }
}