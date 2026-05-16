<?php

namespace App\Domains\BusinessDevelopment\Adjudication\Services;

use App\Domains\BusinessDevelopment\Adjudication\Models\AdjudicationAssessment;
use App\Domains\BusinessDevelopment\Adjudication\Models\AdjudicationSection;
use App\Domains\BusinessDevelopment\Models\BdsPitchSession;
use App\Domains\BusinessDevelopment\Models\BdsIncubatee;
use App\Domains\BusinessDevelopment\Adjudication\Repositories\AdjudicationAssessmentRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AdjudicationAssessmentService
{
    public function __construct(
        protected AdjudicationAssessmentRepositoryInterface $repository
    ) {}

    public function validateScoresAgainstMaxPoints(array $scores, Collection $sections): void
    {
        $sectionsById = $sections->keyBy('id');

        foreach ($scores as $index => $scoreItem) {
            $sectionId = (int) ($scoreItem['section_id'] ?? 0);
            $score = (int) ($scoreItem['score'] ?? 0);
            $section = $sectionsById->get($sectionId);

            if (! $section) {
                throw ValidationException::withMessages([
                    "scores.{$index}.section_id" => ['Selected section is invalid.'],
                ]);
            }

            if ($score < 0 || $score > (int) $section->max_points) {
                throw ValidationException::withMessages([
                    "scores.{$index}.score" => [
                        "Score for {$section->title} must be between 0 and {$section->max_points}.",
                    ],
                ]);
            }
        }
    }

    public function calculateTotal(array $scores): int
    {
        return (int) collect($scores)->sum(fn (array $item) => (int) ($item['score'] ?? 0));
    }

    public function createDraft(array $data, User $actor): AdjudicationAssessment
    {
        Gate::forUser($actor)->authorize('create', AdjudicationAssessment::class);

        return DB::transaction(function () use ($data, $actor) {
            $sections = AdjudicationSection::query()->orderBy('sort_order')->get();
            $this->validateScoresAgainstMaxPoints($data['scores'] ?? [], $sections);

            $scores = $this->normalizeScores($data['scores'] ?? [], $sections);
            $total = $this->calculateTotal($scores);

            $pitchSession = $this->resolvePitchSession($data['pitch_session_id'] ?? null);
            $this->assertPitchSessionEligibility($pitchSession, (int) $data['smme_id'], $actor);

            $assessment = $this->repository->create([
                'smme_id' => (int) $data['smme_id'],
                'pitch_session_id' => $pitchSession?->id,
                'judge_id' => (int) $actor->id,
                'platform_name' => $data['platform_name'],
                'adjudication_date' => $data['adjudication_date'],
                'development_stage' => $data['development_stage'],
                'additional_notes' => $data['additional_notes'] ?? null,
                'status' => 'draft',
                'total_score' => $total,
            ]);

            $this->upsertScores($assessment, $scores);

            return $assessment->load(['judge:id,name', 'smme:id,company_name', 'scores.section', 'sections']);
        });
    }

    public function updateDraft(AdjudicationAssessment $assessment, array $data, User $actor): AdjudicationAssessment
    {
        Gate::forUser($actor)->authorize('update', $assessment);

        return DB::transaction(function () use ($assessment, $data, $actor) {
            $sections = AdjudicationSection::query()->orderBy('sort_order')->get();
            $this->validateScoresAgainstMaxPoints($data['scores'] ?? [], $sections);

            $scores = $this->normalizeScores($data['scores'] ?? [], $sections);
            $total = $this->calculateTotal($scores);

            $pitchSession = $this->resolvePitchSession($data['pitch_session_id'] ?? $assessment->pitch_session_id);
            $this->assertPitchSessionEligibility($pitchSession, (int) $data['smme_id'], $actor);

            $assessment = $this->repository->update($assessment, [
                'smme_id' => (int) $data['smme_id'],
                'pitch_session_id' => $pitchSession?->id,
                'platform_name' => $data['platform_name'],
                'adjudication_date' => $data['adjudication_date'],
                'development_stage' => $data['development_stage'],
                'additional_notes' => $data['additional_notes'] ?? null,
                'total_score' => $total,
            ]);

            $this->upsertScores($assessment, $scores);

            return $assessment->load(['judge:id,name', 'smme:id,company_name', 'scores.section', 'sections']);
        });
    }

    public function submit(AdjudicationAssessment $assessment, User $actor, string $result): AdjudicationAssessment
    {
        Gate::forUser($actor)->authorize('submit', $assessment);

        if ($assessment->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => ['Only draft assessments can be submitted.'],
            ]);
        }

        if (! in_array($result, ['incubated', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'result' => ['Result must be incubated or rejected.'],
            ]);
        }

        DB::transaction(function () use ($assessment, $result, $actor): void {
            $submittedAt = now();
            $assessment->loadMissing(['judge:id,name,email', 'smme:id,company_name,full_name', 'pitchSession:id,title,scheduled_for,status', 'scores.section']);

            $this->repository->update($assessment, [
                'status' => 'submitted',
                'submitted_at' => $submittedAt,
                'submitted_snapshot' => $this->submittedSnapshot($assessment, $actor, $result, $submittedAt),
            ]);

            if ($assessment->pitch_session_id !== null) {
                return;
            }

            $application = $assessment->smme()->firstOrFail();
            $application->update([
                'adjudication_result' => $result,
                'adjudicated_at' => $submittedAt,
                'updated_by' => $actor->id,
            ]);

            if ($result === 'incubated') {
                $this->upsertIncubateeFromApplication($application->toArray(), $actor->id);

                return;
            }

            BdsIncubatee::query()
                ->where('bds_application_id', $application->id)
                ->update([
                    'status' => 'inactive',
                    'updated_by' => $actor->id,
                    'updated_at' => now(),
                ]);
        });

        return $assessment->refresh()->load(['judge:id,name', 'smme:id,company_name', 'scores.section', 'sections']);
    }

    public function unlock(AdjudicationAssessment $assessment, User $actor): AdjudicationAssessment
    {
        Gate::forUser($actor)->authorize('unlock', $assessment);

        DB::transaction(function () use ($assessment, $actor): void {
            $this->repository->update($assessment, [
                'status' => 'draft',
                'submitted_at' => null,
            ]);

            if ($assessment->pitch_session_id !== null) {
                return;
            }

            $assessment->smme()->update([
                'adjudication_result' => null,
                'adjudicated_at' => null,
                'updated_by' => $actor->id,
            ]);
        });

        return $assessment->refresh()->load(['judge:id,name', 'smme:id,company_name', 'scores.section', 'sections']);
    }

    protected function submittedSnapshot(AdjudicationAssessment $assessment, User $actor, string $result, mixed $submittedAt): array
    {
        return [
            'assessment_id' => (int) $assessment->id,
            'pitch_session_id' => $assessment->pitch_session_id ? (int) $assessment->pitch_session_id : null,
            'application' => [
                'id' => (int) $assessment->smme_id,
                'company_name' => $assessment->smme?->company_name,
                'applicant_name' => $assessment->smme?->full_name,
            ],
            'judge' => [
                'id' => (int) $actor->id,
                'name' => $actor->name,
                'email' => $actor->email,
            ],
            'pitch_session' => $assessment->pitchSession ? [
                'id' => (int) $assessment->pitchSession->id,
                'title' => $assessment->pitchSession->title,
                'scheduled_for' => $assessment->pitchSession->scheduled_for?->toDateTimeString(),
                'status' => $assessment->pitchSession->status,
            ] : null,
            'platform_name' => $assessment->platform_name,
            'adjudication_date' => $assessment->adjudication_date?->toDateString(),
            'development_stage' => $assessment->development_stage,
            'result_recommendation' => $result,
            'total_score' => (int) $assessment->total_score,
            'additional_notes' => $assessment->additional_notes,
            'scores' => $assessment->scores
                ->map(fn ($score) => [
                    'section_id' => (int) $score->section_id,
                    'section_title' => $score->section?->title,
                    'max_points' => (int) ($score->section?->max_points ?? 0),
                    'score' => (int) $score->score,
                    'comment' => $score->comment,
                ])
                ->values()
                ->all(),
            'submitted_at' => $submittedAt?->toDateTimeString(),
        ];
    }

    protected function upsertIncubateeFromApplication(array $application, int $actorId): void
    {
        $payload = [
            'bds_application_id' => (int) $application['id'],
            'full_name' => $application['full_name'],
            'id_number' => $application['id_number'],
            'gender' => $application['gender'],
            'mobile_number' => $application['mobile_number'],
            'email' => $application['email'],
            'company_name' => $application['company_name'],
            'company_registration_number' => $application['company_registration_number'],
            'position_in_company' => $application['position_in_company'],
            'majority_shareholding' => $application['majority_shareholding'],
            'current_number_of_employees' => $application['current_number_of_employees'],
            'physical_address' => $application['physical_address'],
            'website_address' => $application['website_address'],
            'years_in_operation' => $application['years_in_operation'],
            'province_id' => $application['province_id'],
            'has_business_plan' => (bool) $application['has_business_plan'],
            'relevant_skill_set' => $application['relevant_skill_set'],
            'technology_product_service' => $application['technology_product_service'],
            'technology_stage_of_development' => $application['technology_stage_of_development'],
            'status' => 'active',
            'incubated_date' => now()->toDateString(),
            'updated_by' => $actorId,
        ];

        $incubatee = BdsIncubatee::query()
            ->where('bds_application_id', $application['id'])
            ->orWhere('id_number', $application['id_number'])
            ->orWhere('company_registration_number', $application['company_registration_number'])
            ->first();

        if ($incubatee) {
            $incubatee->update($payload);

            return;
        }

        BdsIncubatee::query()->create([
            ...$payload,
            'created_by' => $actorId,
        ]);
    }

    protected function upsertScores(AdjudicationAssessment $assessment, array $scores): void
    {
        $payload = collect($scores)
            ->map(fn (array $item) => [
                'assessment_id' => (int) $assessment->id,
                'section_id' => (int) $item['section_id'],
                'score' => (int) $item['score'],
                'comment' => $item['comment'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        DB::table('bd_adjudication_scores')->upsert(
            $payload,
            ['assessment_id', 'section_id'],
            ['score', 'comment', 'updated_at']
        );
    }

    protected function normalizeScores(array $incomingScores, Collection $sections): array
    {
        $scoresBySection = collect($incomingScores)
            ->keyBy(fn (array $score) => (int) ($score['section_id'] ?? 0));

        return $sections
            ->map(function (AdjudicationSection $section) use ($scoresBySection): array {
                $item = $scoresBySection->get((int) $section->id, []);

                return [
                    'section_id' => (int) $section->id,
                    'score' => (int) ($item['score'] ?? 0),
                    'comment' => isset($item['comment']) && $item['comment'] !== '' ? (string) $item['comment'] : null,
                ];
            })
            ->all();
    }

    protected function resolvePitchSession(?int $pitchSessionId): ?BdsPitchSession
    {
        if (! $pitchSessionId) {
            return null;
        }

        return BdsPitchSession::query()
            ->with(['panelists', 'prospects'])
            ->findOrFail($pitchSessionId);
    }

    protected function assertPitchSessionEligibility(?BdsPitchSession $session, int $smmeId, User $actor): void
    {
        if (! $session) {
            return;
        }

        if (! in_array($session->status, ['scheduled', 'in_progress', 'consolidated'], true)) {
            throw ValidationException::withMessages([
                'pitch_session_id' => ['Assessments can only be attached to scheduled or active pitch sessions.'],
            ]);
        }

        if (! $session->prospects->contains(fn ($prospect) => (int) $prospect->bds_application_id === $smmeId)) {
            throw ValidationException::withMessages([
                'smme_id' => ['Selected application is not listed as a prospect in this pitch session.'],
            ]);
        }

        if (! $session->panelists->contains(fn ($panelist) => (int) $panelist->user_id === (int) $actor->id)) {
            throw ValidationException::withMessages([
                'pitch_session_id' => ['Only invited panel members may score prospects in this pitch session.'],
            ]);
        }
    }
}
