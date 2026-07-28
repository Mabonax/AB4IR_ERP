<?php

namespace App\Domains\Meetings\Interfaces;

use App\Domains\Meetings\Models\Meeting;
use Illuminate\Support\Collection;

interface MeetingRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?Meeting;

    public function create(array $data): Meeting;

    public function update(Meeting $meeting, array $data): Meeting;

    public function countByStatus(string $status): int;
}
