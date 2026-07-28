<?php

namespace App\Domains\Committees\Interfaces;

use App\Domains\Committees\Models\Committee;
use Illuminate\Support\Collection;

interface CommitteeRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?Committee;

    public function create(array $data): Committee;

    public function update(Committee $committee, array $data): Committee;

    public function countByStatus(string $status): int;
}
