<?php

namespace App\Domains\Beneficiaries\Services;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Models\NextOfKin;
use App\Domains\Beneficiaries\Repositories\BeneficiaryRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BeneficiaryService
{
    public function __construct(
        protected BeneficiaryRepositoryInterface $repository
    ) {}

    public function list(): Collection
    {
        return $this->repository->all();
    }

    /**
     * Store Beneficiary + Next of Kin (transactional)
     */
    public function store(array $data): Beneficiary
    {
            

        return DB::transaction(function () use ($data) {

            // 1️⃣ Create Next of Kin
            $nextOfKin = NextOfKin::create([
                'name'         => $data['nok_name'],
                'surname'      => $data['nok_surname'],
                'relationship' => $data['nok_relationship'],
                'phone'        => $data['nok_phone'] ?? null,
                'email'        => $data['nok_email'] ?? null,
            ]);

            // 2️⃣ Create Beneficiary
            return $this->repository->create([
                'name'                   => $data['name'],
                'surname'                => $data['surname'],
                'dob'                    => $data['dob'],
                'age'                    => $data['age'],
                'id_number'              => $data['id_number'],
                'email'                  => $data['email'],
                'phone'                  => $data['phone'] ?? null,
                'gender'                 => $data['gender'],
                'street_address'         => $data['street_address'] ?? null,
                'address_line_2'         => $data['address_line_2'] ?? null,
                'city'                   => $data['city'] ?? null,
                'province_id' => ! empty($data['province_id'])
                    ? (int) $data['province_id']
                    : null,
                'postal_code'            => $data['postal_code'] ?? null,
                'highest_qualification'  => $data['highest_qualification'] ?? null,
                'next_of_kin_id'         => $nextOfKin->id,
                'created_by'             => auth()->id(),
            ]);
        });
    }

    /**
     * Update Beneficiary + Next of Kin (transactional)
     */
    public function update(Beneficiary $beneficiary, array $data): Beneficiary
    {
        return DB::transaction(function () use ($beneficiary, $data) {

            // 1️⃣ Update Next of Kin
            if ($beneficiary->nextOfKin) {
                $beneficiary->nextOfKin->update([
                    'name'         => $data['nok_name'],
                    'surname'      => $data['nok_surname'],
                    'relationship' => $data['nok_relationship'],
                    'phone'        => $data['nok_phone'] ?? null,
                    'email'        => $data['nok_email'] ?? null,
                ]);
            }

            // 2️⃣ Update Beneficiary
            return $this->repository->update($beneficiary, [
                'name'                   => $data['name'],
                'surname'                => $data['surname'],
                'dob'                    => $data['dob'],
                'age'                    => $data['age'],
                'id_number'              => $data['id_number'],
                'email'                  => $data['email'],
                'phone'                  => $data['phone'] ?? null,
                'gender'                 => $data['gender'],
                'street_address'         => $data['street_address'] ?? null,
                'address_line_2'         => $data['address_line_2'] ?? null,
                'city'                   => $data['city'] ?? null,
                'province_id' => ! empty($data['province_id'])
                ? (int) $data['province_id']
                : null,
                'postal_code'            => $data['postal_code'] ?? null,
                'highest_qualification'  => $data['highest_qualification'] ?? null,
                'updated_by'             => auth()->id(),
            ]);
        });
    }

    public function delete(Beneficiary $beneficiary): bool
    {
        return $this->repository->delete($beneficiary);
    }
}
