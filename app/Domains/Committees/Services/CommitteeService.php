<?php

namespace App\Domains\Committees\Services;

use App\Domains\Committees\Interfaces\CommitteeRepositoryInterface;
use App\Domains\Committees\Models\Committee;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CommitteeService
{
    public function __construct(
        protected CommitteeRepositoryInterface $repository
    ) {}

    public function findOrFail(int $id): Committee
    {
        $committee = $this->repository->find($id);

        if (! $committee) {
            throw new ModelNotFoundException('Committee not found.');
        }

        return $committee;
    }

    public function create(array $data): Committee
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): Committee
    {
        return $this->repository->update($this->findOrFail($id), $data);
    }

    public function dashboard(): array
    {
        $committees = $this->repository->all();

        return [
            'stats' => [
                'total' => $committees->count(),
                'active' => $this->repository->countByStatus('active'),
                'inactive' => $this->repository->countByStatus('inactive'),
                'total_members' => $committees->sum(fn (Committee $committee) => $committee->members->count()),
                'scheduled_meetings' => $committees->sum(fn (Committee $committee) => $committee->meetings->where('status', 'scheduled')->count()),
            ],
            'committees' => $committees->map(fn (Committee $committee) => [
                'id' => $committee->id,
                'organisation_id' => $committee->organisation_id,
                'organisation_name' => $committee->organisation?->name,
                'name' => $committee->name,
                'description' => $committee->description,
                'chairperson_id' => $committee->chairperson_id,
                'chairperson_name' => $committee->chairperson?->name,
                'secretary_id' => $committee->secretary_id,
                'secretary_name' => $committee->secretary?->name,
                'members_count' => $committee->members->count(),
                'meetings_count' => $committee->meetings->count(),
                'status' => $committee->status,
            ])->values()->all(),
        ];
    }
}
