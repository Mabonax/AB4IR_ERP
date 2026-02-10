<?php

namespace App\Domains\Beneficiaries\Services;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Models\NextOfKin;
use App\Domains\Beneficiaries\Repositories\BeneficiaryRepositoryInterface;
use App\Domains\Projects\Models\ProjectEnrollment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    public function paginateBeneficiaries(): LengthAwarePaginator
    {
        return $this->repository->paginate();
    }

    public function getById(int $id): Beneficiary
    {
        $beneficiary = $this->repository->find($id);

        if (! $beneficiary) {
            throw new ModelNotFoundException('Beneficiary not found.');
        }

        return $beneficiary;
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
            $beneficiary = $this->repository->create([
                'name'                   => $data['name'],
                'surname'                => $data['surname'],
                'dob'                    => $data['dob'],
                'age'                    => $data['age'],
                'id_number'              => $data['id_number'],
                'email'                  => $data['email'],
                'phone'                  => $data['phone'] ?? null,
                'gender'                 => $data['gender'],
                'project_id'             => $data['project_id'],
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

            ProjectEnrollment::updateOrCreate(
                [
                    'project_id' => $data['project_id'],
                    'beneficiary_id' => $beneficiary->id,
                ],
                [
                    'project_location_id' => $data['project_location_id'],
                    'status' => 'enrolled',
                    'enrolled_at' => now(),
                ]
            );

            return $beneficiary;
        });
    }

    /**
     * Update Beneficiary + Next of Kin (transactional)
     */
    public function update(int $id, array $data): Beneficiary
    {
        return DB::transaction(function () use ($id, $data) {
            $beneficiary = $this->getById($id);

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
            $updated = $this->repository->update($beneficiary, [
                'name'                   => $data['name'],
                'surname'                => $data['surname'],
                'dob'                    => $data['dob'],
                'age'                    => $data['age'],
                'id_number'              => $data['id_number'],
                'email'                  => $data['email'],
                'phone'                  => $data['phone'] ?? null,
                'gender'                 => $data['gender'],
                'project_id'             => $data['project_id'],
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

            ProjectEnrollment::updateOrCreate(
                [
                    'project_id' => $data['project_id'],
                    'beneficiary_id' => $beneficiary->id,
                ],
                [
                    'project_location_id' => $data['project_location_id'],
                    'status' => 'enrolled',
                    'enrolled_at' => now(),
                ]
            );

            return $updated;
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $beneficiary = $this->getById($id);

            // 1️⃣ Delete Next of Kin first (if exists)
            if ($beneficiary->nextOfKin) {
                $beneficiary->nextOfKin->delete();
            }

            // 2️⃣ Delete Beneficiary
            return $this->repository->delete($beneficiary);
        });
    }

}

