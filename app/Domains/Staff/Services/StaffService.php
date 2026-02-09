<?php

namespace App\Domains\Staff\Services;

use App\Domains\Staff\Models\StaffMember;
use App\Domains\Staff\Repositories\StaffRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class StaffService
{
    public function __construct(
        protected StaffRepositoryInterface $repository
    ) {}

    public function paginateStaffMembers(): LengthAwarePaginator
    {
        return $this->repository->paginate();
    }

    public function getStaffById(int $id): StaffMember
    {
        $staff = $this->repository->find($id);

        if (! $staff) {
            throw new ModelNotFoundException('Staff member not found.');
        }

        return $staff;
    }

    public function createStaffWithNextOfKin(array $data): StaffMember
    {
        return DB::transaction(function () use ($data) {
            $staff = $this->repository->create($data['staff']);
            $this->repository->createNextOfKin($staff, $data['next_of_kin']);

            return $this->repository->find($staff->id) ?? $staff;
        });
    }

    public function updateStaffWithNextOfKin(int $id, array $data): StaffMember
    {
        return DB::transaction(function () use ($id, $data) {
            $staff = $this->getStaffById($id);

            $this->repository->update($staff, $data['staff']);
            $this->repository->updateNextOfKin($staff, $data['next_of_kin']);

            return $this->repository->find($staff->id) ?? $staff;
        });
    }

    public function deleteStaff(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $staff = $this->getStaffById($id);
            return $this->repository->delete($staff);
        });
    }
}
