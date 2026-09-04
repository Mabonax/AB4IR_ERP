<?php

namespace App\Domains\BusinessDevelopment\Services;

use App\Domains\BusinessDevelopment\Models\BdsApplication;
use App\Domains\BusinessDevelopment\Repositories\BdsApplicationRepositoryInterface;
use App\Domains\Staff\Models\StaffMember;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use ZipArchive;

class BdsApplicationService
{
    protected const STATUS_LABELS = [
        'pending' => 'Pending',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
    ];

    public function __construct(
        protected BdsApplicationRepositoryInterface $repository
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function getById(int $id): BdsApplication
    {
        $application = $this->repository->find($id);

        if (! $application) {
            throw new ModelNotFoundException('BDS application not found.');
        }

        return $application;
    }

    public function assess(int $id, array $data): BdsApplication
    {
        $application = $this->getById($id);
        $user = auth()->user();
        Gate::forUser($user)->authorize('assess', $application);

        $staff = StaffMember::with('department')
            ->where('user_id', (int) $user->id)
            ->first();

        $status = $data['assessment_status'];
        $this->assertAssessmentTransitionAllowed($application, $status);

        return $this->repository->update($application, [
            'assessment_status' => $status,
            'assessed_by_staff_id' => $staff?->id,
            'assessed_at' => now(),
            'pitch_scheduled_at' => $status === 'rejected' ? null : $application->pitch_scheduled_at,
            'pitch_notes' => $status === 'rejected' ? null : $application->pitch_notes,
            'adjudication_result' => $status === 'rejected' ? null : $application->adjudication_result,
            'adjudicated_at' => $status === 'rejected' ? null : $application->adjudicated_at,
            'updated_by' => auth()->id(),
        ]);
    }

    public function getWorkflowSummary(BdsApplication $application): array
    {
        $application->loadMissing(['adjudications']);

        $hasSubmittedAdjudication = (bool) ($application->has_submitted_adjudication
            ?? $application->adjudications->contains(fn ($adjudication) => $adjudication->status === 'submitted'));

        return [
            'assessment_status' => $application->assessment_status,
            'assessment_status_label' => self::STATUS_LABELS[$application->assessment_status] ?? ucfirst($application->assessment_status),
            'assessment' => [
                'accepted' => $this->evaluateAssessmentTransition($application, 'accepted', $hasSubmittedAdjudication),
                'rejected' => $this->evaluateAssessmentTransition($application, 'rejected', $hasSubmittedAdjudication),
            ],
            'pitch' => $this->evaluatePitchReadiness($application, $hasSubmittedAdjudication),
            'adjudication' => $this->evaluateAdjudicationReadiness($application, $hasSubmittedAdjudication),
        ];
    }

    public function importFromFile(UploadedFile $file): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        $rows = match ($extension) {
            'csv', 'txt' => $this->parseCsvFile($file),
            'xlsx' => $this->parseXlsxFile($file),
            default => throw ValidationException::withMessages([
                'file' => ['Unsupported file format. Use CSV or XLSX.'],
            ]),
        };

        $summary = [
            'processed' => 0,
            'created' => 0,
            'duplicates' => 0,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $summary['processed']++;

            try {
                $payload = $this->mapImportRow($row);

                if ($this->isDuplicate($payload['id_number'], $payload['company_registration_number'])) {
                    $summary['duplicates']++;

                    continue;
                }

                $payload['created_by'] = auth()->id();
                $this->repository->create($payload);
                $summary['created']++;
            } catch (\Throwable $exception) {
                $summary['errors'][] = "Row {$line}: {$exception->getMessage()}";
            }
        }

        return $summary;
    }

    protected function mapImportRow(array $row): array
    {
        $mapping = [
            'full name' => 'full_name',
            'id number' => 'id_number',
            'gender' => 'gender',
            'mobile number' => 'mobile_number',
            'your email address' => 'email',
            'name of company' => 'company_name',
            'company registration number' => 'company_registration_number',
            'position in company' => 'position_in_company',
            'majority shareholding' => 'majority_shareholding',
            'current number of employees' => 'current_number_of_employees',
            'physical address' => 'physical_address',
            'website address' => 'website_address',
            'number of years in operation' => 'years_in_operation',
            'in which province are you based' => 'province_name',
            'do you have a business plan business model canvas' => 'has_business_plan',
            'what skill set do you partner have that are relevant to the business' => 'relevant_skill_set',
            'technology product service' => 'technology_product_service',
            'technology product service stage of development' => 'technology_stage_of_development',
            'application date' => 'application_date',
        ];

        $normalized = [];
        foreach ($row as $header => $value) {
            $normalized[$this->normalizeHeader((string) $header)] = is_string($value) ? trim($value) : $value;
        }

        $required = [
            'full name',
            'id number',
            'gender',
            'mobile number',
            'your email address',
            'name of company',
            'company registration number',
            'position in company',
            'majority shareholding',
            'current number of employees',
            'physical address',
            'website address',
            'number of years in operation',
            'in which province are you based',
            'do you have a business plan business model canvas',
            'what skill set do you partner have that are relevant to the business',
            'technology product service',
            'technology product service stage of development',
        ];

        foreach ($required as $header) {
            if (! array_key_exists($header, $normalized) || $normalized[$header] === '' || $normalized[$header] === null) {
                throw new RuntimeException("Missing value for '{$header}'.");
            }
        }

        $provinceName = (string) ($normalized['in which province are you based'] ?? '');
        $provinceId = DB::table('provinces')
            ->whereRaw('LOWER(name) = ?', [Str::lower($provinceName)])
            ->value('id');

        if (! $provinceId) {
            throw new RuntimeException("Province '{$provinceName}' was not found in provinces list.");
        }

        return [
            'full_name' => (string) $normalized['full name'],
            'id_number' => (string) $normalized['id number'],
            'gender' => (string) $normalized['gender'],
            'mobile_number' => (string) $normalized['mobile number'],
            'email' => (string) $normalized['your email address'],
            'company_name' => (string) $normalized['name of company'],
            'company_registration_number' => (string) $normalized['company registration number'],
            'position_in_company' => (string) $normalized['position in company'],
            'majority_shareholding' => (string) $normalized['majority shareholding'],
            'current_number_of_employees' => (int) $normalized['current number of employees'],
            'physical_address' => (string) $normalized['physical address'],
            'website_address' => (string) $normalized['website address'],
            'years_in_operation' => (int) $normalized['number of years in operation'],
            'province_id' => (int) $provinceId,
            'has_business_plan' => $this->toBool($normalized['do you have a business plan business model canvas']),
            'relevant_skill_set' => (string) $normalized['what skill set do you partner have that are relevant to the business'],
            'technology_product_service' => (string) $normalized['technology product service'],
            'technology_stage_of_development' => (string) $normalized['technology product service stage of development'],
            'application_date' => $this->parseDateValue($normalized['application date'] ?? null),
            'assessment_status' => 'pending',
        ];
    }

    protected function assertAssessmentTransitionAllowed(BdsApplication $application, string $targetStatus): void
    {
        $blockers = $this->evaluateAssessmentTransition(
            $application,
            $targetStatus,
            (bool) ($application->has_submitted_adjudication ?? false)
        )['blockers'];

        if ($blockers !== []) {
            throw ValidationException::withMessages([
                'assessment_status' => $blockers,
            ]);
        }
    }

    protected function evaluateAssessmentTransition(
        BdsApplication $application,
        string $targetStatus,
        bool $hasSubmittedAdjudication
    ): array {
        $blockers = [];

        if ($application->adjudication_result !== null) {
            $blockers[] = 'Applications with an adjudication outcome can no longer be reassessed.';
        }

        if ($hasSubmittedAdjudication) {
            $blockers[] = 'Applications with a submitted adjudication cannot be reassessed until that adjudication is unlocked.';
        }

        if ($targetStatus === 'accepted' && $application->assessment_status === 'accepted' && $application->pitch_scheduled_at !== null) {
            $blockers[] = 'This application is already accepted and pitched. Maintain the assessment and continue with adjudication.';
        }

        return [
            'ready' => $blockers === [],
            'blockers' => $blockers,
        ];
    }

    protected function evaluatePitchReadiness(
        BdsApplication $application,
        bool $hasSubmittedAdjudication,
    ): array {
        $blockers = [];

        if ($application->assessment_status !== 'accepted') {
            $blockers[] = 'Only accepted applications can be scheduled through a pitch session.';
        }

        if ($application->adjudication_result !== null) {
            $blockers[] = 'Applications with an adjudication outcome can no longer be added to a pitch session.';
        }

        if ($hasSubmittedAdjudication) {
            $blockers[] = 'Pitch session changes are locked once an adjudication has been submitted.';
        }

        return [
            'ready' => $blockers === [],
            'blockers' => $blockers,
        ];
    }

    protected function evaluateAdjudicationReadiness(BdsApplication $application, bool $hasSubmittedAdjudication): array
    {
        $blockers = [];

        if ($application->assessment_status !== 'accepted') {
            $blockers[] = 'Only accepted applications can proceed to adjudication.';
        }

        if ($application->pitch_scheduled_at === null) {
            $blockers[] = 'A pitch session must be scheduled before adjudication can start.';
        }

        if ($application->adjudication_result !== null) {
            $blockers[] = 'This application already has a final adjudication outcome.';
        }

        if ($hasSubmittedAdjudication) {
            $blockers[] = 'A submitted adjudication already exists for this application.';
        }

        return [
            'ready' => $blockers === [],
            'blockers' => $blockers,
        ];
    }

    protected function isDuplicate(string $idNumber, string $companyRegistrationNumber): bool
    {
        return BdsApplication::query()
            ->where('id_number', $idNumber)
            ->orWhere('company_registration_number', $companyRegistrationNumber)
            ->exists();
    }

    protected function parseCsvFile(UploadedFile $file): array
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

        $rows = [];
        while (($line = fgetcsv($handle)) !== false) {
            if ($this->isEmptyRow($line)) {
                continue;
            }

            $line = array_pad($line, count($headers), null);
            $rows[] = array_combine($headers, $line);
        }

        fclose($handle);

        $this->assertImportHeaders($headers);

        return $rows;
    }

    protected function parseXlsxFile(UploadedFile $file): array
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

        $rows = [];
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

        $headerRow = $rawRows[0];
        $headers = array_values($headerRow);
        $this->assertImportHeaders($headers);

        foreach (array_slice($rawRows, 1) as $rawRow) {
            $line = array_values($rawRow);
            if ($this->isEmptyRow($line)) {
                continue;
            }

            $line = array_pad($line, count($headers), null);
            $rows[] = array_combine($headers, $line);
        }

        return $rows;
    }

    protected function assertImportHeaders(array $headers): void
    {
        $normalizedHeaders = array_map(fn ($header) => $this->normalizeHeader((string) $header), $headers);
        $requiredHeaders = [
            'full name',
            'id number',
            'gender',
            'mobile number',
            'your email address',
            'name of company',
            'company registration number',
            'position in company',
            'majority shareholding',
            'current number of employees',
            'physical address',
            'website address',
            'number of years in operation',
            'in which province are you based',
            'do you have a business plan business model canvas',
            'what skill set do you partner have that are relevant to the business',
            'technology product service',
            'technology product service stage of development',
        ];

        $missing = array_values(array_diff($requiredHeaders, $normalizedHeaders));
        if (! empty($missing)) {
            throw ValidationException::withMessages([
                'file' => ['Missing required headers: '.implode(', ', $missing)],
            ]);
        }
    }

    protected function normalizeHeader(string $header): string
    {
        $header = Str::lower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', ' ', $header) ?? $header;

        return trim($header);
    }

    protected function parseDateValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            // Excel serial date conversion with 1900 leap-year offset.
            $base = Carbon::create(1899, 12, 30, 0, 0, 0, 'UTC');

            return $base->addDays((int) $value)->toDateString();
        }

        return Carbon::parse((string) $value)->toDateString();
    }

    protected function toBool(mixed $value): bool
    {
        $normalized = Str::lower(trim((string) $value));

        return in_array($normalized, ['yes', 'y', 'true', '1'], true);
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

    protected function isEmptyRow(array $line): bool
    {
        foreach ($line as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
