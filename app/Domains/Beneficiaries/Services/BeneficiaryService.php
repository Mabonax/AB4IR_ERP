<?php

namespace App\Domains\Beneficiaries\Services;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Beneficiaries\Repositories\BeneficiaryRepositoryInterface;
use App\Domains\Projects\Services\ProjectEnrollmentConsistencyService;
use App\Models\NextOfKin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BeneficiaryService
{
    public function __construct(
        protected BeneficiaryRepositoryInterface $repository,
        protected ProjectEnrollmentConsistencyService $enrollmentConsistency
    ) {}

    public function list(): Collection
    {
        return $this->repository->all();
    }

    public function paginateBeneficiaries(?int $programId = null, ?int $projectId = null): LengthAwarePaginator
    {
        return $this->repository->paginate($programId, $projectId);
    }

    public function getById(int $id): Beneficiary
    {
        $beneficiary = $this->repository->find($id);

        if (! $beneficiary) {
            throw new ModelNotFoundException('Beneficiary not found.');
        }

        return $beneficiary;
    }

    public function store(array $data): Beneficiary
    {
        return DB::transaction(function () use ($data) {
            $projectId = (int) $data['project_id'];
            $projectLocationId = (int) $data['project_location_id'];
            $attendanceStatus = (string) ($data['attendance_status'] ?? 'active');

            $nextOfKin = NextOfKin::create([
                'name' => $data['nok_name'],
                'surname' => $data['nok_surname'],
                'relationship' => $data['nok_relationship'],
                'phone' => $data['nok_phone'] ?? null,
                'email' => $data['nok_email'] ?? null,
            ]);

            $beneficiary = $this->repository->create([
                'name' => $data['name'],
                'surname' => $data['surname'],
                'dob' => $data['dob'],
                'age' => $data['age'],
                'id_number' => $data['id_number'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'gender' => $data['gender'],
                'project_id' => $projectId,
                'street_address' => $data['street_address'] ?? null,
                'address_line_2' => $data['address_line_2'] ?? null,
                'city' => $data['city'] ?? null,
                'province_id' => ! empty($data['province_id']) ? (int) $data['province_id'] : null,
                'postal_code' => $data['postal_code'] ?? null,
                'highest_qualification' => $data['highest_qualification'] ?? null,
                'attendance_status' => $attendanceStatus,
                'next_of_kin_id' => $nextOfKin->id,
                'created_by' => auth()->id(),
            ]);

            $this->enrollmentConsistency->syncBeneficiaryEnrollment(
                $beneficiary,
                $projectId,
                $projectLocationId,
                $this->enrollmentStatusFromAttendanceStatus($attendanceStatus)
            );

            return $beneficiary;
        });
    }

    public function update(int $id, array $data): Beneficiary
    {
        return DB::transaction(function () use ($id, $data) {
            $beneficiary = $this->getById($id);
            $projectId = (int) $data['project_id'];
            $projectLocationId = (int) $data['project_location_id'];
            $attendanceStatus = (string) ($data['attendance_status'] ?? 'active');

            if ($beneficiary->nextOfKin) {
                $beneficiary->nextOfKin->update([
                    'name' => $data['nok_name'],
                    'surname' => $data['nok_surname'],
                    'relationship' => $data['nok_relationship'],
                    'phone' => $data['nok_phone'] ?? null,
                    'email' => $data['nok_email'] ?? null,
                ]);
            } else {
                $beneficiary->next_of_kin_id = NextOfKin::create([
                    'name' => $data['nok_name'],
                    'surname' => $data['nok_surname'],
                    'relationship' => $data['nok_relationship'],
                    'phone' => $data['nok_phone'] ?? null,
                    'email' => $data['nok_email'] ?? null,
                ])->id;
            }

            $updated = $this->repository->update($beneficiary, [
                'name' => $data['name'],
                'surname' => $data['surname'],
                'dob' => $data['dob'],
                'age' => $data['age'],
                'id_number' => $data['id_number'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'gender' => $data['gender'],
                'project_id' => $projectId,
                'street_address' => $data['street_address'] ?? null,
                'address_line_2' => $data['address_line_2'] ?? null,
                'city' => $data['city'] ?? null,
                'province_id' => ! empty($data['province_id']) ? (int) $data['province_id'] : null,
                'postal_code' => $data['postal_code'] ?? null,
                'highest_qualification' => $data['highest_qualification'] ?? null,
                'attendance_status' => $attendanceStatus,
                'next_of_kin_id' => $beneficiary->next_of_kin_id,
                'updated_by' => auth()->id(),
            ]);

            $this->enrollmentConsistency->syncBeneficiaryEnrollment(
                $updated,
                $projectId,
                $projectLocationId,
                $this->enrollmentStatusFromAttendanceStatus($attendanceStatus)
            );

            return $updated;
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $beneficiary = $this->getById($id);

            if ($beneficiary->nextOfKin) {
                $beneficiary->nextOfKin->delete();
            }

            return $this->repository->delete($beneficiary);
        });
    }

    protected function enrollmentStatusFromAttendanceStatus(string $attendanceStatus): string
    {
        return $attendanceStatus === 'dropout' ? 'dropped' : 'enrolled';
    }
}
