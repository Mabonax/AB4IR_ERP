<?php

namespace App\Domains\Beneficiaries\Repositories;

use App\Domains\Beneficiaries\Models\Beneficiary;
use Illuminate\Support\Collection;

class BeneficiaryRepository implements BeneficiaryRepositoryInterface
{
    public function all(): Collection
    {
        return Beneficiary::latest()->get();
    }

    public function find(int $id): ?Beneficiary
    {
        return Beneficiary::find($id);
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
