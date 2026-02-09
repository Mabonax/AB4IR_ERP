<?php

namespace App\Domains\Stakeholders\Services;

use App\Domains\Stakeholders\Models\Stakeholder;
use App\Domains\Stakeholders\Repositories\StakeholderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class StakeholderService
{
    public function __construct(
        protected StakeholderRepositoryInterface $repository
    ) {}

    public function paginateStakeholders(): LengthAwarePaginator
    {
        return $this->repository->paginate();
    }

    public function getStakeholderById(int $id): Stakeholder
    {
        $stakeholder = $this->repository->find($id);

        if (! $stakeholder) {
            throw new ModelNotFoundException('Stakeholder not found.');
        }

        return $stakeholder;
    }

    public function createStakeholderWithContact(array $data): Stakeholder
    {
        return DB::transaction(function () use ($data) {
            $stakeholder = $this->repository->create($data['stakeholder']);
            $this->repository->createContact($stakeholder, $data['contact']);

            return $this->repository->find($stakeholder->id) ?? $stakeholder;
        });
    }

    public function updateStakeholderWithContact(int $id, array $data): Stakeholder
    {
        return DB::transaction(function () use ($id, $data) {
            $stakeholder = $this->getStakeholderById($id);

            $this->repository->update($stakeholder, $data['stakeholder']);
            $this->repository->updateContact($stakeholder, $data['contact']);

            return $this->repository->find($stakeholder->id) ?? $stakeholder;
        });
    }

    public function deleteStakeholder(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $stakeholder = $this->getStakeholderById($id);
            return $this->repository->delete($stakeholder);
        });
    }
}
