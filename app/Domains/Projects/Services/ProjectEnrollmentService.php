<?php

namespace App\Domains\Projects\Services;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Repositories\ProjectEnrollmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class ProjectEnrollmentService
{
    public function __construct(
        protected ProjectEnrollmentRepositoryInterface $repository,
        protected ProjectEnrollmentConsistencyService $consistency
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
            $beneficiary = Beneficiary::query()->findOrFail((int) $data['beneficiary_id']);
            $this->consistency->assertBeneficiaryBelongsToProject($beneficiary, (int) $data['project_id']);
            $this->consistency->assertProjectAcceptsBeneficiaryPlacement((int) $data['project_id'], (int) $beneficiary->project_id);
            $this->consistency->assertLocationBelongsToProject((int) $data['project_id'], (int) $data['project_location_id']);

            return $this->repository->create($data);
        });
    }

    public function updateEnrollment(int $id, array $data): ProjectEnrollment
    {
        return DB::transaction(function () use ($id, $data) {
            $enrollment = $this->getEnrollmentById($id);
            $beneficiary = Beneficiary::query()->findOrFail((int) $data['beneficiary_id']);
            $this->consistency->assertBeneficiaryBelongsToProject($beneficiary, (int) $data['project_id']);
            $this->consistency->assertProjectAcceptsBeneficiaryPlacement((int) $data['project_id'], (int) $beneficiary->project_id);
            $this->consistency->assertLocationBelongsToProject((int) $data['project_id'], (int) $data['project_location_id']);

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
