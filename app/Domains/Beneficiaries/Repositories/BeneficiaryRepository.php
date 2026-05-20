<?php

namespace App\Domains\Beneficiaries\Repositories;

use App\Domains\Beneficiaries\Models\Beneficiary;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BeneficiaryRepository implements BeneficiaryRepositoryInterface
{
    protected function baseQuery()
    {
        return Beneficiary::with([
            'nextOfKin',
            'project.program',
            'projectEnrollments.project.program',
            'projectEnrollments.location.province',
        ])->latest();
    }

    public function paginate(?int $programId = null, ?int $projectId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->baseQuery();

        if ($projectId) {
            $query->whereHas('projectEnrollments', fn ($enrollmentQuery) => $enrollmentQuery->where('project_id', $projectId));
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function all(): Collection
    {
        return $this->baseQuery()->get();
    }

    public function find(int $id): ?Beneficiary
    {
        return $this->baseQuery()->find($id);
    }

    public function create(array $data): Beneficiary
    {
        return Beneficiary::create($data);
    }

    public function update(Beneficiary $beneficiary, array $data): Beneficiary
    {
        $beneficiary->update($data);

        return $beneficiary;
    }

    public function delete(Beneficiary $beneficiary): bool
    {
        return $beneficiary->delete();
    }
}
