<?php

namespace App\Domains\Projects\Repositories;

use App\Domains\Projects\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Project::with(['program', 'sponsor', 'partners', 'projectManager'])
            ->latest()
            ->paginate($perPage);
    }

    public function all(): Collection
    {
        return Project::with(['program', 'sponsor', 'partners', 'projectManager'])
            ->latest()
            ->get();
    }

    public function find(int $id): ?Project
    {
        return Project::with(['program', 'sponsor', 'partners', 'projectManager'])->find($id);
    }

    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project;
    }

    public function delete(Project $project): bool
    {
        return $project->delete();
    }
}
