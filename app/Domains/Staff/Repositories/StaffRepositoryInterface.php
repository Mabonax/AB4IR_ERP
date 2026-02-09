<?php

namespace App\Domains\Staff\Repositories;

use App\Domains\Staff\Models\StaffMember;
use App\Domains\Staff\Models\StaffNextOfKin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface StaffRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function all(): Collection;

    public function find(int $id): ?StaffMember;

    public function create(array $data): StaffMember;

    public function update(StaffMember $staff, array $data): StaffMember;

    public function delete(StaffMember $staff): bool;

    public function createNextOfKin(StaffMember $staff, array $data): StaffNextOfKin;

    public function updateNextOfKin(StaffMember $staff, array $data): StaffNextOfKin;
}
