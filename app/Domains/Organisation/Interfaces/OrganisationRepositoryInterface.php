<?php

namespace App\Domains\Organisation\Interfaces;

use App\Domains\Organisation\Models\Organisation;
use Illuminate\Support\Collection;

interface OrganisationRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?Organisation;

    public function create(array $data): Organisation;

    public function update(Organisation $organisation, array $data): Organisation;

    public function countAll(): int;

    public function countActive(): int;

    public function countByType(string $type): int;
}
