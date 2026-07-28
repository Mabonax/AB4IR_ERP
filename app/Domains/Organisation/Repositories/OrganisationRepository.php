<?php

namespace App\Domains\Organisation\Repositories;

use App\Domains\Organisation\Interfaces\OrganisationRepositoryInterface;
use App\Domains\Organisation\Models\Organisation;
use Illuminate\Support\Collection;

class OrganisationRepository implements OrganisationRepositoryInterface
{
    public function all(): Collection
    {
        return Organisation::query()->orderBy('name')->get();
    }

    public function find(int $id): ?Organisation
    {
        return Organisation::query()->find($id);
    }

    public function create(array $data): Organisation
    {
        return Organisation::query()->create($data);
    }

    public function update(Organisation $organisation, array $data): Organisation
    {
        $organisation->update($data);

        return $organisation;
    }

    public function countAll(): int
    {
        return Organisation::query()->count();
    }

    public function countActive(): int
    {
        return Organisation::query()->where('status', 'active')->count();
    }

    public function countByType(string $type): int
    {
        return Organisation::query()->where('organisation_type', $type)->count();
    }
}
