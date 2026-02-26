<?php

namespace App\Domains\BusinessDevelopment\Adjudication\Services;

use App\Domains\BusinessDevelopment\Adjudication\Models\AdjudicationAssessment;
use App\Domains\BusinessDevelopment\Adjudication\Models\AdjudicationSection;
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

            $assessment = $this->repository->create([
                'smme_id' => (int) $data['smme_id'],
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

        return DB::transaction(function () use ($assessment, $data) {
            $sections = AdjudicationSection::query()->orderBy('sort_order')->get();
            $this->validateScoresAgainstMaxPoints($data['scores'] ?? [], $sections);

            $scores = $this->normalizeScores($data['scores'] ?? [], $sections);
            $total = $this->calculateTotal($scores);

            $assessment = $this->repository->update($assessment, [
                'smme_id' => (int) $data['smme_id'],
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

    public function submit(AdjudicationAssessment $assessment, User $actor): AdjudicationAssessment
    {
        Gate::forUser($actor)->authorize('submit', $assessment);

        if ($assessment->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => ['Only draft assessments can be submitted.'],
            ]);
        }

        $this->repository->update($assessment, [
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return $assessment->refresh()->load(['judge:id,name', 'smme:id,company_name', 'scores.section', 'sections']);
    }

    public function unlock(AdjudicationAssessment $assessment, User $actor): AdjudicationAssessment
    {
        Gate::forUser($actor)->authorize('unlock', $assessment);

        $this->repository->update($assessment, [
            'status' => 'draft',
            'submitted_at' => null,
        ]);

        return $assessment->refresh()->load(['judge:id,name', 'smme:id,company_name', 'scores.section', 'sections']);
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
}
