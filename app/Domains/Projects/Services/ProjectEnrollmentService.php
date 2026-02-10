<?php

namespace App\Domains\Projects\Services;

use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Repositories\ProjectEnrollmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class ProjectEnrollmentService
{
    public function __construct(
        protected ProjectEnrollmentRepositoryInterface $repository
    ) {}

    public function paginateEnrollments(): LengthAwarePaginator
    {
        return $this->repository->paginate();
    }

    public function getEnrollmentById(int $id): ProjectEnrollment
    {
        $enrollment = $this->repository->find($id);

        if (! $enrollment) {
            throw new ModelNotFoundException('Project enrollment not found.');
        }

        return $enrollment;
    }

    public function createEnrollment(array $data): ProjectEnrollment
    {
        return DB::transaction(function () use ($data) {
            return $this->repository->create($data);
        });
    }

    public function updateEnrollment(int $id, array $data): ProjectEnrollment
    {
        return DB::transaction(function () use ($id, $data) {
            $enrollment = $this->getEnrollmentById($id);
            return $this->repository->update($enrollment, $data);
        });
    }

    public function deleteEnrollment(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $enrollment = $this->getEnrollmentById($id);
            return $this->repository->delete($enrollment);
        });
    }
}
