<?php

namespace App\Domains\Beneficiaries\Services;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Beneficiaries\Repositories\BeneficiaryRepositoryInterface;
use App\Domains\Beneficiaries\Support\BeneficiaryIdentityMatcher;
use App\Domains\Members\Models\Member;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\ProjectEnrollmentConsistencyService;
use App\Models\NextOfKin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use ZipArchive;

class BeneficiaryService
{
    public function __construct(
        protected BeneficiaryRepositoryInterface $repository,
        protected ProjectEnrollmentConsistencyService $enrollmentConsistency,
        protected BeneficiaryIdentityMatcher $identityMatcher
    ) {}

    public function list(): Collection
    {
        return $this->repository->all();
    }

    public function paginateBeneficiaries(?int $projectId = null): LengthAwarePaginator
    {
        return $this->repository->paginate($projectId);
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
            $programId = $this->resolveProgramId($data, $projectId);
            $member = $this->syncMemberProfile($data);
            $nextOfKin = $this->upsertNextOfKin(null, $data);

            $beneficiary = $this->repository->create([
                'member_id' => $member?->id,
                'beneficiary_number' => $this->nextBeneficiaryNumber(),
                'name' => $this->requiredString($data['name']),
                'surname' => $this->requiredString($data['surname']),
                'dob' => $this->normalizeDate($data['dob'] ?? null),
                'age' => $this->normalizeAge($data['age'] ?? null, $data['dob'] ?? null),
                'id_number' => $this->nullableString($data['id_number'] ?? null),
                'email' => $this->normalizeEmail($data['email'] ?? null),
                'phone' => $this->nullableString($data['phone'] ?? null),
                'gender' => $this->nullableString($data['gender'] ?? null),
                'project_id' => $projectId,
                'program_id' => $programId,
                'enrolment_date' => $this->normalizeDate($data['enrolment_date'] ?? now()->toDateString()),
                'exit_date' => $this->normalizeDate($data['exit_date'] ?? null),
                'participation_status' => $this->normalizeParticipationStatus($data['participation_status'] ?? 'registered'),
                'placement_status' => $this->nullableString($data['placement_status'] ?? null),
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
            $programId = $this->resolveProgramId($data, $projectId);
            $member = $this->syncMemberProfile($data, $beneficiary->member);
            $nextOfKin = $this->upsertNextOfKin($beneficiary->nextOfKin, $data);

            $updated = $this->repository->update($beneficiary, [
                'member_id' => $member?->id,
                'name' => $this->requiredString($data['name']),
                'surname' => $this->requiredString($data['surname']),
                'dob' => $this->normalizeDate($data['dob'] ?? null),
                'age' => $this->normalizeAge($data['age'] ?? null, $data['dob'] ?? null),
                'id_number' => $this->nullableString($data['id_number'] ?? null),
                'email' => $this->normalizeEmail($data['email'] ?? null),
                'phone' => $this->nullableString($data['phone'] ?? null),
                'gender' => $this->nullableString($data['gender'] ?? null),
                'project_id' => $projectId,
                'program_id' => $programId,
                'enrolment_date' => $this->normalizeDate($data['enrolment_date'] ?? $beneficiary->enrolment_date?->format('Y-m-d')),
                'exit_date' => $this->normalizeDate($data['exit_date'] ?? null),
                'participation_status' => $this->normalizeParticipationStatus($data['participation_status'] ?? $beneficiary->participation_status ?? 'registered'),
                'placement_status' => $this->nullableString($data['placement_status'] ?? null),
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
                $this->enrollmentStatusFromAttendanceStatus($attendanceStatus),
                currentProjectId: (int) $beneficiary->project_id
            );

            return $updated;
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $beneficiary = $this->getById($id);

            return $this->repository->delete($beneficiary);
        });
    }

    public function importFromFile(UploadedFile $file, int $projectId, int $projectLocationId): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        $rows = match ($extension) {
            'csv', 'txt' => $this->parseImportCsvFile($file),
            'xlsx' => $this->parseImportXlsxFile($file),
            default => throw ValidationException::withMessages([
                'file' => ['Unsupported file format. Use CSV or XLSX.'],
            ]),
        };

        $summary = [
            'processed' => 0,
            'created' => 0,
            'matched_existing' => 0,
            'rejected_duplicates' => 0,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $summary['processed']++;

            try {
                $payload = $this->mapImportRow($row, $projectId, $projectLocationId);
                $match = $this->identityMatcher->findMatch($payload);

                if ($match) {
                    if ($match->trashed()) {
                        $summary['rejected_duplicates']++;
                        $summary['errors'][] = "Row {$line}: matches archived beneficiary #{$match->id}. Restore or update the existing record instead.";

                        continue;
                    }

                    if ((int) $match->project_id === $projectId) {
                        $currentEnrollment = $match->projectEnrollments()
                            ->where('project_id', $projectId)
                            ->first();

                        if ($currentEnrollment && (int) $currentEnrollment->project_location_id === $projectLocationId) {
                            $summary['matched_existing']++;

                            continue;
                        }

                        $summary['rejected_duplicates']++;
                        $summary['errors'][] = "Row {$line}: matches beneficiary #{$match->id} on the same project but a different location. Review and update that record manually.";

                        continue;
                    }

                    $summary['rejected_duplicates']++;
                    $summary['errors'][] = "Row {$line}: matches beneficiary #{$match->id} already assigned to another project. Use the transfer workflow instead of importing a duplicate.";

                    continue;
                }

                $this->store($payload);
                $summary['created']++;
            } catch (\Throwable $exception) {
                $summary['errors'][] = "Row {$line}: {$exception->getMessage()}";
            }
        }

        return $summary;
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

    protected function mapImportRow(array $row, int $projectId, int $projectLocationId): array
    {
        $normalized = [];
        foreach ($row as $header => $value) {
            $normalized[$this->normalizeImportHeader((string) $header)] = is_string($value) ? trim($value) : $value;
        }

        foreach (['name', 'surname'] as $requiredHeader) {
            if (! array_key_exists($requiredHeader, $normalized) || trim((string) $normalized[$requiredHeader]) === '') {
                throw new RuntimeException("Missing value for '{$requiredHeader}'.");
            }
        }

        $provinceId = $this->resolveProvinceId($normalized['province'] ?? $normalized['province_name'] ?? null);
        $attendanceStatus = $this->normalizeAttendanceStatus($normalized['attendance_status'] ?? null);
        $dob = $this->nullableString($normalized['dob'] ?? null);
        $age = $this->nullableString($normalized['age'] ?? null);

        if ($dob === null && $age === null && $this->nullableString($normalized['id_number'] ?? null) === null) {
            throw new RuntimeException('Provide at least one of dob, age, or id_number so imported records can be identified safely.');
        }

        return [
            'name' => $this->requiredString($normalized['name']),
            'surname' => $this->requiredString($normalized['surname']),
            'dob' => $dob,
            'age' => $age,
            'id_number' => $this->nullableString($normalized['id_number'] ?? null),
            'email' => $this->normalizeEmail($normalized['email'] ?? null),
            'phone' => $this->nullableString($normalized['phone'] ?? null),
            'gender' => $this->normalizeGender($normalized['gender'] ?? null),
            'project_id' => $projectId,
            'project_location_id' => $projectLocationId,
            'street_address' => $this->nullableString($normalized['street_address'] ?? $normalized['address'] ?? null),
            'address_line_2' => $this->nullableString($normalized['address_line_2'] ?? null),
            'city' => $this->nullableString($normalized['city'] ?? null),
            'province_id' => $provinceId,
            'postal_code' => $this->nullableString($normalized['postal_code'] ?? null),
            'highest_qualification' => $this->nullableString($normalized['highest_qualification'] ?? $normalized['qualification'] ?? null),
            'attendance_status' => $attendanceStatus,
            'nok_name' => $this->nullableString($normalized['nok_name'] ?? $normalized['next_of_kin_name'] ?? null),
            'nok_surname' => $this->nullableString($normalized['nok_surname'] ?? $normalized['next_of_kin_surname'] ?? null),
            'nok_relationship' => $this->nullableString($normalized['nok_relationship'] ?? $normalized['next_of_kin_relationship'] ?? null),
            'nok_phone' => $this->nullableString($normalized['nok_phone'] ?? $normalized['next_of_kin_phone'] ?? null),
            'nok_email' => $this->normalizeEmail($normalized['nok_email'] ?? $normalized['next_of_kin_email'] ?? null),
        ];
    }

    protected function parseImportCsvFile(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            throw ValidationException::withMessages([
                'file' => ['Could not read CSV file.'],
            ]);
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);
            throw ValidationException::withMessages([
                'file' => ['CSV file is empty.'],
            ]);
        }

        $this->assertImportHeaders($headers);

        $rows = [];
        while (($line = fgetcsv($handle)) !== false) {
            if ($this->isEmptyImportRow($line)) {
                continue;
            }

            $line = array_pad($line, count($headers), null);
            $rows[] = array_combine($headers, $line);
        }

        fclose($handle);

        return $rows;
    }

    protected function parseImportXlsxFile(UploadedFile $file): array
    {
        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            throw ValidationException::withMessages([
                'file' => ['Could not open XLSX file.'],
            ]);
        }

        $sheetPath = 'xl/worksheets/sheet1.xml';
        if ($zip->locateName($sheetPath) === false) {
            $zip->close();
            throw ValidationException::withMessages([
                'file' => ['XLSX worksheet not found. Expected sheet1.'],
            ]);
        }

        $sheetXml = $zip->getFromName($sheetPath);
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        $sharedStrings = [];
        if ($sharedStringsXml !== false) {
            $sharedXml = simplexml_load_string($sharedStringsXml);
            if ($sharedXml !== false && isset($sharedXml->si)) {
                foreach ($sharedXml->si as $item) {
                    if (isset($item->t)) {
                        $sharedStrings[] = (string) $item->t;

                        continue;
                    }

                    $text = '';
                    if (isset($item->r)) {
                        foreach ($item->r as $run) {
                            $text .= (string) ($run->t ?? '');
                        }
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        $xml = simplexml_load_string((string) $sheetXml);
        if ($xml === false) {
            throw ValidationException::withMessages([
                'file' => ['Could not parse XLSX sheet XML.'],
            ]);
        }

        $namespaces = $xml->getNamespaces(true);
        $sheetData = $xml->children($namespaces[''] ?? null)->sheetData ?? null;
        if (! $sheetData) {
            throw ValidationException::withMessages([
                'file' => ['No worksheet data found in XLSX file.'],
            ]);
        }

        $rawRows = [];
        foreach ($sheetData->row as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $ref = (string) ($cell['r'] ?? '');
                $columnIndex = $this->columnIndexFromReference($ref);
                $type = (string) ($cell['t'] ?? '');
                $cellValue = '';

                if (isset($cell->v)) {
                    $raw = (string) $cell->v;
                    $cellValue = $type === 's'
                        ? ($sharedStrings[(int) $raw] ?? '')
                        : $raw;
                } elseif (isset($cell->is->t)) {
                    $cellValue = (string) $cell->is->t;
                }

                $values[$columnIndex] = $cellValue;
            }

            if (! empty($values)) {
                ksort($values);
                $rawRows[] = $values;
            }
        }

        if (empty($rawRows)) {
            throw ValidationException::withMessages([
                'file' => ['XLSX file is empty.'],
            ]);
        }

        $headers = array_values($rawRows[0]);
        $this->assertImportHeaders($headers);

        $rows = [];
        foreach (array_slice($rawRows, 1) as $rawRow) {
            $line = array_values($rawRow);
            if ($this->isEmptyImportRow($line)) {
                continue;
            }

            $line = array_pad($line, count($headers), null);
            $rows[] = array_combine($headers, $line);
        }

        return $rows;
    }

    protected function assertImportHeaders(array $headers): void
    {
        $normalizedHeaders = array_map(fn ($header) => $this->normalizeImportHeader((string) $header), $headers);
        $requiredHeaders = ['name', 'surname'];

        $missing = array_values(array_diff($requiredHeaders, $normalizedHeaders));
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'file' => ['Missing required headers: '.implode(', ', $missing)],
            ]);
        }
    }

    protected function normalizeImportHeader(string $header): string
    {
        $header = Str::lower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? $header;

        return trim($header, '_');
    }

    protected function resolveProvinceId(mixed $value): ?int
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $normalized = trim($value);

        if (ctype_digit($normalized)) {
            return (int) $normalized;
        }

        $provinceId = DB::table('provinces')
            ->whereRaw('LOWER(name) = ?', [Str::lower($normalized)])
            ->value('id');

        if (! $provinceId) {
            throw new RuntimeException("Province '{$normalized}' was not found in provinces list.");
        }

        return (int) $provinceId;
    }

    protected function normalizeAttendanceStatus(mixed $value): string
    {
        $normalized = Str::lower((string) $this->nullableString($value));

        return match ($normalized) {
            'dropout', 'dropped' => 'dropout',
            default => 'active',
        };
    }

    protected function normalizeGender(mixed $value): ?string
    {
        $normalized = Str::lower((string) $this->nullableString($value));

        return match ($normalized) {
            'male', 'm' => 'male',
            'female', 'f' => 'female',
            default => null,
        };
    }

    protected function normalizeParticipationStatus(mixed $value): string
    {
        $normalized = Str::lower((string) $this->nullableString($value));

        return match ($normalized) {
            'enrolled', 'active', 'completed', 'withdrawn', 'suspended' => $normalized,
            default => 'registered',
        };
    }

    protected function resolveProgramId(array $data, int $projectId): ?int
    {
        if (! empty($data['program_id'])) {
            return (int) $data['program_id'];
        }

        return Project::query()->whereKey($projectId)->value('program_id');
    }

    protected function syncMemberProfile(array $data, ?Member $existingMember = null): ?Member
    {
        $memberId = ! empty($data['member_id']) ? (int) $data['member_id'] : null;
        $idNumber = $this->nullableString($data['id_number'] ?? null);
        $email = $this->normalizeEmail($data['email'] ?? null);

        $member = $memberId
            ? Member::query()->find($memberId)
            : $existingMember;

        if (! $member) {
            if ($idNumber || $email) {
                $member = Member::query()
                    ->where(function ($query) use ($idNumber, $email) {
                        if ($idNumber) {
                            $query->where('id_number', $idNumber);
                        }

                        if ($email) {
                            $idNumber
                                ? $query->orWhere('email', $email)
                                : $query->where('email', $email);
                        }
                    })
                    ->first();
            }
        }

        if (! $member && $idNumber === null) {
            return null;
        }

        $payload = [
            'first_name' => $this->requiredString($data['name']),
            'last_name' => $this->requiredString($data['surname']),
            'id_number' => $idNumber,
            'date_of_birth' => $this->normalizeDate($data['dob'] ?? null),
            'gender' => $this->nullableString($data['gender'] ?? null),
            'phone' => $this->nullableString($data['phone'] ?? null),
            'email' => $email,
            'physical_address' => $this->nullableString($data['street_address'] ?? null),
            'province_id' => ! empty($data['province_id']) ? (int) $data['province_id'] : null,
            'member_type' => $this->nullableString($data['member_type'] ?? null) ?? 'Beneficiary',
            'status' => 'active',
        ];

        if ($member) {
            $member->fill(array_filter($payload, fn ($value) => $value !== null));
            $member->save();

            return $member->fresh();
        }

        return Member::query()->create($payload);
    }

    protected function nextBeneficiaryNumber(): string
    {
        $latestId = (int) Beneficiary::query()->withTrashed()->max('id') + 1;

        return 'BEN-'.str_pad((string) $latestId, 5, '0', STR_PAD_LEFT);
    }

    protected function columnIndexFromReference(string $reference): int
    {
        if (! preg_match('/^[A-Z]+/', strtoupper($reference), $matches)) {
            return 0;
        }

        $letters = $matches[0];
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }

    protected function isEmptyImportRow(array $line): bool
    {
        foreach ($line as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
