<?php

namespace App\Domains\Projects\Services;

use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Repositories\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    public function __construct(
        protected ProjectRepositoryInterface $repository
    ) {}

    public function paginateProjects(): LengthAwarePaginator
    {
        return $this->repository->paginate();
    }

    public function getProjectById(int $id): Project
    {
        $project = $this->repository->find($id);

        if (! $project) {
            throw new ModelNotFoundException('Project not found.');
        }

        return $project;
    }

    public function createProject(array $data): Project
    {
        return DB::transaction(function () use ($data) {
            return $this->repository->create($data);
        });
    }

    public function updateProject(int $id, array $data): Project
    {
        return DB::transaction(function () use ($id, $data) {
            $project = $this->getProjectById($id);
            return $this->repository->update($project, $data);
        });
    }

    public function deleteProject(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $project = $this->getProjectById($id);
            return $this->repository->delete($project);
        });
    }
}
