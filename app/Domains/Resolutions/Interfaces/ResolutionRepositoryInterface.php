<?php

namespace App\Domains\Resolutions\Interfaces;

use App\Domains\Resolutions\Models\Resolution;
use Illuminate\Support\Collection;

interface ResolutionRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?Resolution;

    public function create(array $data): Resolution;

    public function update(Resolution $resolution, array $data): Resolution;

    public function countByStatus(string $status): int;
}
