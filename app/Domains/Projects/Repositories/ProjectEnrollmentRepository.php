<?php

namespace App\Domains\Projects\Repositories;

use App\Domains\Projects\Models\ProjectEnrollment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectEnrollmentRepository implements ProjectEnrollmentRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return ProjectEnrollment::with(['project', 'beneficiary'])
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id): ?ProjectEnrollment
    {
        return ProjectEnrollment::with(['project', 'beneficiary'])->find($id);
    }

    public function create(array $data): ProjectEnrollment
    {
        return ProjectEnrollment::create($data);
    }

    public function update(ProjectEnrollment $enrollment, array $data): ProjectEnrollment
    {
        $enrollment->update($data);
        return $enrollment;
    }

    public function delete(ProjectEnrollment $enrollment): bool
    {
        return $enrollment->delete();
    }
}
