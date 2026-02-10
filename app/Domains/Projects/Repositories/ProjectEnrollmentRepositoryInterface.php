<?php

namespace App\Domains\Projects\Repositories;

use App\Domains\Projects\Models\ProjectEnrollment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProjectEnrollmentRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?ProjectEnrollment;

    public function create(array $data): ProjectEnrollment;

    public function update(ProjectEnrollment $enrollment, array $data): ProjectEnrollment;

    public function delete(ProjectEnrollment $enrollment): bool;
}
