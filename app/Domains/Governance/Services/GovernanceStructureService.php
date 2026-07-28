<?php

namespace App\Domains\Governance\Services;

use App\Domains\Governance\Interfaces\GovernanceStructureRepositoryInterface;
use App\Domains\Governance\Models\GovernanceStructure;
use App\Domains\Meetings\Models\Meeting;
use App\Domains\Resolutions\Models\Resolution;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GovernanceStructureService
{
    public function __construct(
        protected GovernanceStructureRepositoryInterface $repository
    ) {}

    public function findOrFail(int $id): GovernanceStructure
    {
        $structure = $this->repository->find($id);

        if (! $structure) {
            throw new ModelNotFoundException('Governance structure not found.');
        }

        return $structure;
    }

    public function create(array $data): GovernanceStructure
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): GovernanceStructure
    {
        return $this->repository->update($this->findOrFail($id), $data);
    }

    public function dashboard(): array
    {
        $structures = $this->repository->all();
        $upcomingMeetings = Meeting::query()
            ->whereDate('meeting_date', '>=', now()->toDateString())
            ->count();
        $openResolutions = Resolution::query()
            ->whereIn('status', ['open', 'in_progress', 'overdue'])
            ->count();

        return [
            'stats' => [
                'total' => $structures->count(),
                'active' => $this->repository->countByStatus('active'),
                'inactive' => $this->repository->countByStatus('inactive'),
                'upcoming_meetings' => $upcomingMeetings,
                'open_resolutions' => $openResolutions,
                'policies_due_for_review' => 0,
            ],
            'structures' => $structures->map(fn (GovernanceStructure $structure) => [
                'id' => $structure->id,
                'organisation_id' => $structure->organisation_id,
                'organisation_name' => $structure->organisation?->name,
                'name' => $structure->name,
                'description' => $structure->description,
                'status' => $structure->status,
            ])->values()->all(),
        ];
    }

    public function executiveWidget(): array
    {
        $upcomingMeetings = Meeting::query()
            ->whereDate('meeting_date', '>=', now()->toDateString())
            ->count();
        $openResolutions = Resolution::query()
            ->whereIn('status', ['open', 'in_progress', 'overdue'])
            ->count();

        return [
            'upcoming_meetings' => $upcomingMeetings,
            'open_resolutions' => $openResolutions,
            'policies_due_for_review' => 0,
        ];
    }
}
