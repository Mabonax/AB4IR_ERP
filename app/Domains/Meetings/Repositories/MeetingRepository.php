<?php

namespace App\Domains\Meetings\Repositories;

use App\Domains\Meetings\Interfaces\MeetingRepositoryInterface;
use App\Domains\Meetings\Models\Meeting;
use Illuminate\Support\Collection;

class MeetingRepository implements MeetingRepositoryInterface
{
    public function all(): Collection
    {
        return Meeting::query()
            ->with(['organisation', 'committee', 'attendance.user', 'resolutions'])
            ->orderBy('meeting_date')
            ->get();
    }

    public function find(int $id): ?Meeting
    {
        return Meeting::query()->find($id);
    }

    public function create(array $data): Meeting
    {
        return Meeting::query()->create($data);
    }

    public function update(Meeting $meeting, array $data): Meeting
    {
        $meeting->update($data);

        return $meeting->refresh();
    }

    public function countByStatus(string $status): int
    {
        return Meeting::query()->where('status', $status)->count();
    }
}
