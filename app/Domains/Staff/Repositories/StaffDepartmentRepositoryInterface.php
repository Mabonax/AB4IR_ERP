<?php

namespace App\Domains\Staff\Repositories;

use App\Domains\Staff\Models\StaffDepartment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StaffDepartmentRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?StaffDepartment;

    public function create(array $data): StaffDepartment;

    public function update(StaffDepartment $department, array $data): StaffDepartment;

    public function delete(StaffDepartment $department): bool;
}
