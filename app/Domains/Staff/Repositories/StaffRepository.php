<?php

namespace App\Domains\Staff\Repositories;

use App\Domains\Staff\Models\StaffMember;
use App\Domains\Staff\Models\StaffNextOfKin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StaffRepository implements StaffRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return StaffMember::with(['department', 'nextOfKin', 'user', 'manager'])
            ->latest()
            ->paginate($perPage);
    }

    public function all(): Collection
    {
        return StaffMember::with(['department', 'nextOfKin', 'user', 'manager'])
            ->latest()
            ->get();
    }

    public function find(int $id): ?StaffMember
    {
        return StaffMember::with(['department', 'nextOfKin', 'user', 'manager'])->find($id);
    }

    public function create(array $data): StaffMember
    {
        return StaffMember::create($data);
    }

    public function update(StaffMember $staff, array $data): StaffMember
    {
        $staff->update($data);
        return $staff;
    }

    public function delete(StaffMember $staff): bool
    {
        return $staff->delete();
    }

    public function createNextOfKin(StaffMember $staff, array $data): StaffNextOfKin
    {
        return $staff->nextOfKin()->create($data);
    }

    public function updateNextOfKin(StaffMember $staff, array $data): StaffNextOfKin
    {
        if ($staff->nextOfKin) {
            $staff->nextOfKin->update($data);
            return $staff->nextOfKin;
        }

        return $staff->nextOfKin()->create($data);
    }
}
