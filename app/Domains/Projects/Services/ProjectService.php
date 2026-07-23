<?php

namespace App\Domains\Projects\Services;

use App\Domains\Documents\Services\DocumentFolderService;
use App\Domains\Projects\Models\ProgramMilestoneTemplate;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Models\ProjectMilestone;
use App\Domains\Projects\Repositories\ProjectRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectService
{
    protected const STATUS_TRANSITIONS = [
        'planned' => ['active', 'on_hold', 'completed', 'cancelled'],
        'active' => ['on_hold', 'completed', 'cancelled'],
        'on_hold' => ['active', 'cancelled', 'completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    protected const STATUS_LABELS = [
        'planned' => 'Planned',
        'active' => 'Active',
        'completed' => 'Completed',
        'on_hold' => 'On Hold',
        'cancelled' => 'Cancelled',
    ];

    public function __construct(
        protected ProjectRepositoryInterface $repository,
        protected ProjectHistoryService $historyService,
        protected DocumentFolderService $documentFolderService,
    ) {}

    public function paginateProjects(): LengthAwarePaginator
    {
        return $this->repository->paginate();
    }

    public function getProjectById(int $id): Project
    {
        $project = $this->repository->find($id);

        if (! $project) {
            throw new ModelNotFoundException('Project not found.');
        }

        return $project;
    }

    public function createProject(array $data, ?User $actor = null): Project
    {
        return DB::transaction(function () use ($data, $actor) {
            $data = $this->normalizeProjectPayload($data);
            $partnerIds = collect($data['partner_stakeholder_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();
            $projectData = collect($data)->except('partner_stakeholder_ids')->all();

            $project = $this->repository->create($projectData);
            $project->partners()->sync($partnerIds);
            $this->documentFolderService->createDefaultProjectFolders($project, $actor);

            $this->syncProgramMilestones($project);
            $this->assertProjectStatusReadiness($project, null, $data);

            $project->load(['partners']);
            $this->historyService->record(
                $project,
                'created',
                'Project created.',
                $actor,
                [
                    'status' => $project->status,
                    'project_manager_id' => $project->project_manager_id,
                    'partner_count' => count($partnerIds),
                ]
            );

            return $project;
        });
    }

    public function syncProgramMilestones(Project $project): void
    {
        $templates = ProgramMilestoneTemplate::where('program_id', $project->program_id)
            ->orderBy('sort_order')
            ->get();

        foreach ($templates as $template) {
            ProjectMilestone::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'program_milestone_template_id' => $template->id,
                ],
                [
                    'title' => $template->title,
                    'description' => $template->description,
                    'sort_order' => $template->sort_order,
                    'max_score' => $template->max_score,
                ]
            );
        }
    }

    public function updateProject(int $id, array $data, ?User $actor = null): Project
    {
        return DB::transaction(function () use ($id, $data, $actor) {
            $project = $this->getProjectById($id);
            $data = $this->normalizeProjectPayload($data, $project);
            $original = $project->replicate();
            $partnerIds = collect($data['partner_stakeholder_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();
            $projectData = collect($data)->except('partner_stakeholder_ids')->all();
            $programChanged = (int) ($data['program_id'] ?? $project->program_id) !== (int) $project->program_id;

            $this->assertProgramChangeIsSafe($project, $programChanged);
            $this->assertProjectStatusReadiness($project, $project->status, $data);

            $updated = $this->repository->update($project, $projectData);
            $updated->partners()->sync($partnerIds);
            $this->documentFolderService->createDefaultProjectFolders($updated, $actor);

            if ($programChanged) {
                $this->replaceProgramMilestones($updated);
            }

            $changes = array_keys($updated->getChanges());

            if (($data['status'] ?? $project->status) === 'completed') {
                $this->markProjectEnrollmentsCompleted($updated);
            }

            $fresh = $updated->fresh(['locations', 'milestones', 'partners']);

            if ($actor !== null || $changes !== []) {
                $this->historyService->record(
                    $fresh,
                    'updated',
                    'Project updated.',
                    $actor,
                    [
                        'from_status' => $original->status,
                        'to_status' => $fresh->status,
                        'changed_fields' => $changes,
                        'partner_count' => count($partnerIds),
                    ]
                );
            }

            return $fresh;
        });
    }

    public function deleteProject(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $project = $this->getProjectById($id);

            return $this->repository->delete($project);
        });
    }

    public function getStatusSummary(Project $project): array
    {
        $project->loadMissing(['locations', 'milestones']);

        $currentStatus = (string) $project->status;
        $allowedTransitions = self::STATUS_TRANSITIONS[$currentStatus] ?? [];

        return [
            'current' => $currentStatus,
            'current_label' => $this->statusLabel($currentStatus),
            'allowed_transitions' => collect($allowedTransitions)
                ->map(fn (string $status) => [
                    'status' => $status,
                    'label' => $this->statusLabel($status),
                    ...$this->evaluateStatusReadiness($project, $status),
                ])
                ->values()
                ->all(),
            'readiness' => [
                'active' => $this->evaluateStatusReadiness($project, 'active'),
                'completed' => $this->evaluateStatusReadiness($project, 'completed'),
            ],
        ];
    }

    protected function assertProjectStatusReadiness(Project $project, ?string $originalStatus, array $data): void
    {
        $targetStatus = (string) ($data['status'] ?? $project->status);
        $currentStatus = $originalStatus ?? $project->status;

        if ($originalStatus !== null) {
            $this->assertAllowedStatusTransition($currentStatus, $targetStatus);
        }

        if ($targetStatus === 'active' && ($originalStatus === null || $currentStatus !== 'active')) {
            $this->assertProjectCanActivate($project);
        }

        if ($targetStatus === 'completed' && ($originalStatus === null || $currentStatus !== 'completed')) {
            $this->assertProjectCanComplete($project, $data);
        }
    }

    protected function assertAllowedStatusTransition(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        if (! in_array($to, self::STATUS_TRANSITIONS[$from] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => ["Project status cannot transition from {$from} to {$to}."],
            ]);
        }
    }

    protected function assertProjectCanActivate(Project $project): void
    {
        $blockers = $this->activationBlockers($project);

        if ($blockers !== []) {
            throw ValidationException::withMessages([
                'status' => $blockers,
            ]);
        }
    }

    protected function assertProjectCanComplete(Project $project, array $data): void
    {
        $blockers = $this->completionBlockers($project, $data);

        if ($blockers !== []) {
            $messages = ['status' => $blockers];

            if (in_array('A completed project must have an end date.', $blockers, true)) {
                $messages['end_date'] = ['A completed project must have an end date.'];
            }

            throw ValidationException::withMessages($messages);
        }
    }

    protected function markProjectEnrollmentsCompleted(Project $project): void
    {
        ProjectEnrollment::query()
            ->where('project_id', $project->id)
            ->whereIn('status', ['enrolled', 'completed'])
            ->whereHas('beneficiary', fn ($query) => $query
                ->where('attendance_status', 'active')
                ->where(function ($statusQuery) {
                    $statusQuery->whereNull('status')->orWhere('status', 'enrolled');
                }))
            ->update([
                'status' => 'completed',
                'updated_at' => now(),
            ]);
    }

    protected function evaluateStatusReadiness(Project $project, string $targetStatus): array
    {
        $blockers = match ($targetStatus) {
            'active' => $this->activationBlockers($project),
            'completed' => $this->completionBlockers($project),
            default => [],
        };

        return [
            'ready' => $blockers === [],
            'blockers' => $blockers,
        ];
    }

    protected function activationBlockers(Project $project): array
    {
        $project->loadMissing(['locations', 'milestones']);

        $blockers = [];

        if (! $project->project_manager_id) {
            $blockers[] = 'A project needs a project manager before it can become active.';
        }

        if (! $project->locations->isNotEmpty()) {
            $blockers[] = 'A project needs at least one location before it can become active.';
        }

        if (! $project->milestones->isNotEmpty()) {
            $blockers[] = 'A project needs at least one milestone before it can become active.';
        }

        return $blockers;
    }

    protected function completionBlockers(Project $project, array $data = []): array
    {
        $project->loadMissing(['locations', 'milestones']);

        $blockers = [];
        $endDate = $data['end_date'] ?? $project->end_date?->format('Y-m-d');

        if (! $endDate) {
            $blockers[] = 'A completed project must have an end date.';
        }

        $blockers = [...$blockers, ...$this->activationBlockers($project)];

        $milestoneIds = $project->milestones->pluck('id')->all();

        if ($milestoneIds === []) {
            return array_values(array_unique($blockers));
        }

        $activeEnrollments = ProjectEnrollment::query()
            ->where('project_id', $project->id)
            ->whereIn('status', ['enrolled', 'completed'])
            ->whereHas('beneficiary', fn ($query) => $query
                ->where('attendance_status', 'active')
                ->where(function ($statusQuery) {
                    $statusQuery->whereNull('status')->orWhere('status', 'enrolled');
                }))
            ->get(['beneficiary_id', 'project_location_id']);

        if ($activeEnrollments->isEmpty()) {
            $blockers[] = 'A completed project must still have active beneficiary delivery records before closure.';

            return array_values(array_unique($blockers));
        }

        $missingAssessments = 0;

        foreach ($activeEnrollments as $enrollment) {
            foreach ($milestoneIds as $milestoneId) {
                $completed = DB::table('project_milestone_assessments')
                    ->where('project_milestone_id', $milestoneId)
                    ->where('project_location_id', $enrollment->project_location_id)
                    ->where('beneficiary_id', $enrollment->beneficiary_id)
                    ->where('status', 'completed')
                    ->exists();

                if (! $completed) {
                    $missingAssessments++;
                }
            }
        }

        if ($missingAssessments > 0) {
            $blockers[] = "All active beneficiaries must complete every project milestone before the project can be completed. {$missingAssessments} completion record(s) are still missing.";
        }

        return array_values(array_unique($blockers));
    }

    protected function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    protected function normalizeProjectPayload(array $data, ?Project $project = null): array
    {
        $data['status'] = (string) ($data['status'] ?? $project?->status ?? 'planned');

        if (array_key_exists('project_manager_id', $data) && blank($data['project_manager_id'])) {
            $data['project_manager_id'] = null;
        }

        return $data;
    }

    protected function assertProgramChangeIsSafe(Project $project, bool $programChanged): void
    {
        if (! $programChanged) {
            return;
        }

        $hasOperationalData = $project->locations()->exists()
            || $project->enrollments()->exists()
            || DB::table('project_milestone_assessments')
                ->whereIn('project_milestone_id', ProjectMilestone::query()->where('project_id', $project->id)->select('id'))
                ->exists()
            || $project->closure()->exists()
            || $project->reports()->exists();

        if (! $hasOperationalData) {
            return;
        }

        throw ValidationException::withMessages([
            'program_id' => ['Project program cannot change after locations, enrollments, assessments, or governance records already exist.'],
        ]);
    }

    protected function replaceProgramMilestones(Project $project): void
    {
        ProjectMilestone::query()->where('project_id', $project->id)->delete();
        $this->syncProgramMilestones($project->fresh());
    }
}
