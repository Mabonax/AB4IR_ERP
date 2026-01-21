<?php

namespace App\Domains\Beneficiaries\Services;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Beneficiaries\Repositories\BeneficiaryRepositoryInterface;
use Illuminate\Support\Collection;

class BeneficiaryService
{
    public function __construct(
        protected BeneficiaryRepositoryInterface $repository
    ) {}

    public function list(): Collection
    {
        return $repository = $this->repository->all();
    }

    public function store(array $data): Beneficiary
    {
        return $this->repository->create($data);
    }

    public function update(Beneficiary $beneficiary, array $data): Beneficiary
    {
        return $this->repository->update($beneficiary, $data);
    }

    public function delete(Beneficiary $beneficiary): bool
    {
        return $this->repository->delete($beneficiary);
    }
}
