<?php

namespace App\Domains\Meetings\Services;

use App\Domains\Meetings\Interfaces\MeetingRepositoryInterface;
use App\Domains\Meetings\Models\Meeting;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MeetingService
{
    public function __construct(
        protected MeetingRepositoryInterface $repository
    ) {}

    public function findOrFail(int $id): Meeting
    {
        $meeting = $this->repository->find($id);

        if (! $meeting) {
            throw new ModelNotFoundException('Meeting not found.');
        }

        return $meeting;
    }

    public function create(array $data): Meeting
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): Meeting
    {
        return $this->repository->update($this->findOrFail($id), $data);
    }

    public function dashboard(): array
    {
        $meetings = $this->repository->all();

        return [
            'stats' => [
                'total' => $meetings->count(),
                'draft' => $this->repository->countByStatus('draft'),
                'scheduled' => $this->repository->countByStatus('scheduled'),
                'completed' => $this->repository->countByStatus('completed'),
                'cancelled' => $this->repository->countByStatus('cancelled'),
            ],
            'meetings' => $meetings->map(fn (Meeting $meeting) => [
                'id' => $meeting->id,
                'organisation_id' => $meeting->organisation_id,
                'organisation_name' => $meeting->organisation?->name,
                'committee_id' => $meeting->committee_id,
                'committee_name' => $meeting->committee?->name,
                'meeting_number' => $meeting->meeting_number,
                'title' => $meeting->title,
                'meeting_date' => $meeting->meeting_date?->toDateString(),
                'location' => $meeting->location,
                'agenda' => $meeting->agenda,
                'minutes' => $meeting->minutes,
                'attendance_count' => $meeting->attendance->count(),
                'resolution_count' => $meeting->resolutions->count(),
                'status' => $meeting->status,
            ])->values()->all(),
        ];
    }

    public function upcomingCount(): int
    {
        return Meeting::query()
            ->whereDate('meeting_date', '>=', now()->toDateString())
            ->count();
    }
}
