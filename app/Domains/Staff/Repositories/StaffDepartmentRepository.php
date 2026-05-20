<?php

namespace App\Domains\Staff\Repositories;

use App\Domains\Staff\Models\StaffDepartment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StaffDepartmentRepository implements StaffDepartmentRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return StaffDepartment::latest()->paginate($perPage);
    }

    public function find(int $id): ?StaffDepartment
    {
        return StaffDepartment::find($id);
    }

    public function create(array $data): StaffDepartment
    {
        return StaffDepartment::create($data);
    }

    public function update(StaffDepartment $department, array $data): StaffDepartment
    {
        $department->update($data);

        return $department;
    }

    public function delete(StaffDepartment $department): bool
    {
        return $department->delete();
    }
}
