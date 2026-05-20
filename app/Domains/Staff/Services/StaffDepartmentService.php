<?php

namespace App\Domains\Staff\Services;

use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Repositories\StaffDepartmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class StaffDepartmentService
{
    public function __construct(
        protected StaffDepartmentRepositoryInterface $repository
    ) {}

    public function paginateDepartments(): LengthAwarePaginator
    {
        return $this->repository->paginate();
    }

    public function getDepartmentById(int $id): StaffDepartment
    {
        $department = $this->repository->find($id);

        if (! $department) {
            throw new ModelNotFoundException('Staff department not found.');
        }

        return $department;
    }

    public function createDepartment(array $data): StaffDepartment
    {
        return DB::transaction(function () use ($data) {
            return $this->repository->create($data);
        });
    }

    public function updateDepartment(int $id, array $data): StaffDepartment
    {
        return DB::transaction(function () use ($id, $data) {
            $department = $this->getDepartmentById($id);

            return $this->repository->update($department, $data);
        });
    }

    public function deleteDepartment(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $department = $this->getDepartmentById($id);

            return $this->repository->delete($department);
        });
    }
}
