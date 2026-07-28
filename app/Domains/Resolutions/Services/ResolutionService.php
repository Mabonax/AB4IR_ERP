<?php

namespace App\Domains\Resolutions\Services;

use App\Domains\Resolutions\Interfaces\ResolutionRepositoryInterface;
use App\Domains\Resolutions\Models\Resolution;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ResolutionService
{
    public function __construct(
        protected ResolutionRepositoryInterface $repository
    ) {}

    public function findOrFail(int $id): Resolution
    {
        $resolution = $this->repository->find($id);

        if (! $resolution) {
            throw new ModelNotFoundException('Resolution not found.');
        }

        return $resolution;
    }

    public function create(array $data): Resolution
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): Resolution
    {
        return $this->repository->update($this->findOrFail($id), $data);
    }

    public function dashboard(): array
    {
        $resolutions = $this->repository->all();
        $overdue = Resolution::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['completed'])
            ->count();

        return [
            'stats' => [
                'total' => $resolutions->count(),
                'open' => $this->repository->countByStatus('open'),
                'in_progress' => $this->repository->countByStatus('in_progress'),
                'completed' => $this->repository->countByStatus('completed'),
                'overdue' => $overdue,
            ],
            'resolutions' => $resolutions->map(fn (Resolution $resolution) => [
                'id' => $resolution->id,
                'organisation_id' => $resolution->organisation_id,
                'organisation_name' => $resolution->organisation?->name,
                'meeting_id' => $resolution->meeting_id,
                'meeting_title' => $resolution->meeting?->title,
                'resolution_number' => $resolution->resolution_number,
                'title' => $resolution->title,
                'description' => $resolution->description,
                'owner_id' => $resolution->owner_id,
                'owner_name' => $resolution->owner?->name,
                'due_date' => $resolution->due_date?->toDateString(),
                'status' => $resolution->status,
            ])->values()->all(),
        ];
    }

    public function openCount(): int
    {
        return Resolution::query()
            ->whereIn('status', ['open', 'in_progress', 'overdue'])
            ->count();
    }
}
