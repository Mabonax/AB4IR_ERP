<?php

namespace App\Domains\Beneficiaries\Services;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Beneficiaries\Repositories\BeneficiaryRepositoryInterface;
use App\Domains\Projects\Services\ProjectEnrollmentConsistencyService;
use App\Models\NextOfKin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
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
            $nextOfKin = $this->upsertNextOfKin(null, $data);

            $beneficiary = $this->repository->create([
                'name' => $this->requiredString($data['name']),
                'surname' => $this->requiredString($data['surname']),
                'dob' => $this->normalizeDate($data['dob'] ?? null),
                'age' => $this->normalizeAge($data['age'] ?? null, $data['dob'] ?? null),
                'id_number' => $this->nullableString($data['id_number'] ?? null),
                'email' => $this->normalizeEmail($data['email'] ?? null),
                'phone' => $this->nullableString($data['phone'] ?? null),
                'gender' => $this->nullableString($data['gender'] ?? null),
                'project_id' => $projectId,
                'street_address' => $this->nullableString($data['street_address'] ?? null),
                'address_line_2' => $this->nullableString($data['address_line_2'] ?? null),
                'city' => $this->nullableString($data['city'] ?? null),
                'province_id' => ! empty($data['province_id']) ? (int) $data['province_id'] : null,
                'postal_code' => $this->nullableString($data['postal_code'] ?? null),
                'highest_qualification' => $this->nullableString($data['highest_qualification'] ?? null),
                'attendance_status' => $attendanceStatus,
                'next_of_kin_id' => $nextOfKin?->id,
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
            $nextOfKin = $this->upsertNextOfKin($beneficiary->nextOfKin, $data);

            $updated = $this->repository->update($beneficiary, [
                'name' => $this->requiredString($data['name']),
                'surname' => $this->requiredString($data['surname']),
                'dob' => $this->normalizeDate($data['dob'] ?? null),
                'age' => $this->normalizeAge($data['age'] ?? null, $data['dob'] ?? null),
                'id_number' => $this->nullableString($data['id_number'] ?? null),
                'email' => $this->normalizeEmail($data['email'] ?? null),
                'phone' => $this->nullableString($data['phone'] ?? null),
                'gender' => $this->nullableString($data['gender'] ?? null),
                'project_id' => $projectId,
                'street_address' => $this->nullableString($data['street_address'] ?? null),
                'address_line_2' => $this->nullableString($data['address_line_2'] ?? null),
                'city' => $this->nullableString($data['city'] ?? null),
                'province_id' => ! empty($data['province_id']) ? (int) $data['province_id'] : null,
                'postal_code' => $this->nullableString($data['postal_code'] ?? null),
                'highest_qualification' => $this->nullableString($data['highest_qualification'] ?? null),
                'attendance_status' => $attendanceStatus,
                'next_of_kin_id' => $nextOfKin?->id,
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

    protected function upsertNextOfKin(?NextOfKin $nextOfKin, array $data): ?NextOfKin
    {
        $payload = $this->nextOfKinPayload($data);

        if ($payload === null) {
            if ($nextOfKin) {
                $nextOfKin->delete();
            }

            return null;
        }

        if ($nextOfKin) {
            $nextOfKin->update($payload);

            return $nextOfKin->fresh();
        }

        return NextOfKin::create($payload);
    }

    protected function nextOfKinPayload(array $data): ?array
    {
        $payload = [
            'name' => $this->nullableString($data['nok_name'] ?? null),
            'surname' => $this->nullableString($data['nok_surname'] ?? null),
            'relationship' => $this->nullableString($data['nok_relationship'] ?? null),
            'phone' => $this->nullableString($data['nok_phone'] ?? null),
            'email' => $this->nullableString($data['nok_email'] ?? null),
        ];

        foreach ($payload as $value) {
            if ($value !== null) {
                return $payload;
            }
        }

        return null;
    }

    protected function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    protected function normalizeDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }

    protected function normalizeAge(mixed $value, mixed $dob): ?int
    {
        if ($value !== null && $value !== '') {
            return (int) $value;
        }

        $normalizedDob = $this->normalizeDate($dob);

        if ($normalizedDob === null) {
            return null;
        }

        return Carbon::parse($normalizedDob)->age;
    }

    protected function normalizeEmail(mixed $value): ?string
    {
        $normalized = $this->nullableString($value);

        return $normalized === null ? null : strtolower($normalized);
    }

    protected function requiredString(mixed $value): string
    {
        return trim((string) $value);
    }
}
