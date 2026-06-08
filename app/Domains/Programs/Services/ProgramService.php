<?php

namespace App\Domains\Programs\Services;

use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\ProjectProgressService;
use App\Domains\Programs\Models\Program;
use App\Domains\Programs\Repositories\ProgramRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProgramService
{
    public function __construct(
        protected ProgramRepositoryInterface $repository,
        protected ProjectProgressService $progressService
    ) {}

    public function list(): Collection
    {
        return $this->repository->all();
    }

    public function paginatePrograms(): LengthAwarePaginator
    {
        return $this->repository->paginate();
    }

    public function getById(int $id): Program
    {
        $program = $this->repository->find($id);

        if (! $program) {
            throw new ModelNotFoundException('Program not found.');
        }

        return $program;
    }

    public function getOverview(int $id): array
    {
        $program = Program::query()
            ->withCount(['projects', 'milestoneTemplates'])
            ->with([
                'milestoneTemplates',
                'projects.program',
                'projects.sponsor',
                'projects.projectManager',
                'projects.locations.facilitator',
                'projects.locations.province',
                'projects.locations.enrollments.beneficiary',
                'projects.locations.milestoneAssessments',
                'projects.locations.attendanceRegisters.entries',
                'projects.enrollments.beneficiary',
                'projects.milestones',
            ])
            ->find($id);

        if (! $program) {
            throw new ModelNotFoundException('Program not found.');
        }

        $projectSnapshots = $program->projects
            ->sortBy([
                ['start_date', 'asc'],
                ['created_at', 'asc'],
            ])
            ->values()
            ->map(fn (Project $project) => $this->mapProjectSnapshot($project))
            ->values();

        $beneficiaryIds = $program->projects
            ->flatMap(fn (Project $project) => $project->enrollments->pluck('beneficiary_id'))
            ->filter()
            ->unique()
            ->values();

        $yearlyImpact = $projectSnapshots
            ->groupBy('year')
            ->map(function (Collection $projects, string $year) {
                return [
                    'year' => $year,
                    'projects' => $projects->count(),
                    'beneficiaries' => (int) $projects->sum('total_beneficiaries'),
                    'active_beneficiaries' => (int) $projects->sum('active_beneficiaries'),
                    'locations' => (int) $projects->sum('total_locations'),
                    'completed_projects' => (int) $projects->where('status', 'completed')->count(),
                ];
            })
            ->sortKeys()
            ->values();

        return [
            'program' => $program,
            'stats' => [
                'total_projects' => $projectSnapshots->count(),
                'active_projects' => (int) $projectSnapshots->where('status', 'active')->count(),
                'completed_projects' => (int) $projectSnapshots->where('status', 'completed')->count(),
                'total_locations' => (int) $projectSnapshots->sum('total_locations'),
                'milestone_templates_count' => (int) $program->milestone_templates_count,
                'tracked_beneficiaries' => (int) $projectSnapshots->sum('total_beneficiaries'),
                'unique_beneficiaries' => $beneficiaryIds->count(),
                'active_beneficiaries' => (int) $projectSnapshots->sum('active_beneficiaries'),
                'completed_beneficiaries' => (int) $projectSnapshots->sum('completed_beneficiaries'),
                'dropped_beneficiaries' => (int) $projectSnapshots->sum('dropped_beneficiaries'),
                'blocked_locations' => (int) $projectSnapshots->sum('blocked_locations'),
                'active_years' => $yearlyImpact->count(),
                'average_milestone_completion_rate' => round((float) $projectSnapshots->avg('milestone_completion_rate'), 2),
                'average_beneficiary_completion_rate' => round((float) $projectSnapshots->avg('beneficiary_completion_rate'), 2),
                'average_attendance_rate' => round((float) $projectSnapshots->avg('attendance_rate'), 2),
            ],
            'yearly_impact' => $yearlyImpact->all(),
            'projects' => $projectSnapshots->all(),
        ];
    }

    public function summarizePortfolio(): array
    {
        $programs = Program::query()
            ->withCount(['projects', 'milestoneTemplates'])
            ->with([
                'projects.program',
                'projects.sponsor',
                'projects.projectManager',
                'projects.locations.facilitator',
                'projects.locations.province',
                'projects.locations.enrollments.beneficiary',
                'projects.locations.milestoneAssessments',
                'projects.locations.attendanceRegisters.entries',
                'projects.enrollments.beneficiary',
                'projects.milestones',
            ])
            ->orderBy('title')
            ->get();

        $programSummaries = $programs
            ->map(function (Program $program) {
                $overview = $this->summarizeProgram($program);

                return [
                    'id' => $program->id,
                    'title' => $program->title,
                    'slug' => $program->slug,
                    'projects_count' => $overview['stats']['total_projects'],
                    'active_projects' => $overview['stats']['active_projects'],
                    'completed_projects' => $overview['stats']['completed_projects'],
                    'total_locations' => $overview['stats']['total_locations'],
                    'unique_beneficiaries' => $overview['stats']['unique_beneficiaries'],
                    'tracked_beneficiaries' => $overview['stats']['tracked_beneficiaries'],
                    'active_years' => $overview['stats']['active_years'],
                    'average_milestone_completion_rate' => $overview['stats']['average_milestone_completion_rate'],
                    'average_beneficiary_completion_rate' => $overview['stats']['average_beneficiary_completion_rate'],
                    'average_attendance_rate' => $overview['stats']['average_attendance_rate'],
                    'blocked_locations' => $overview['stats']['blocked_locations'],
                ];
            })
            ->values();

        return [
            'programs' => $programSummaries->all(),
            'stats' => [
                'tracked_programs' => $programSummaries->count(),
                'active_projects' => (int) $programSummaries->sum('active_projects'),
                'completed_projects' => (int) $programSummaries->sum('completed_projects'),
                'tracked_beneficiaries' => (int) $programSummaries->sum('tracked_beneficiaries'),
                'unique_beneficiaries' => (int) $programSummaries->sum('unique_beneficiaries'),
                'total_locations' => (int) $programSummaries->sum('total_locations'),
                'active_years' => (int) $programSummaries->sum('active_years'),
                'blocked_locations' => (int) $programSummaries->sum('blocked_locations'),
                'average_milestone_completion_rate' => round((float) $programSummaries->avg('average_milestone_completion_rate'), 2),
                'average_beneficiary_completion_rate' => round((float) $programSummaries->avg('average_beneficiary_completion_rate'), 2),
                'average_attendance_rate' => round((float) $programSummaries->avg('average_attendance_rate'), 2),
            ],
        ];
    }

    public function create(array $data): Program
    {
        return DB::transaction(function () use ($data) {
            return $this->repository->create($data);
        });
    }

    public function update(int $id, array $data): Program
    {
        return DB::transaction(function () use ($id, $data) {
            $program = $this->getById($id);

            return $this->repository->update($program, $data);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $program = $this->getById($id);

            return $this->repository->delete($program);
        });
    }

    protected function mapProjectSnapshot(Project $project): array
    {
        $progress = $this->progressService->summarizeProject($project);
        $summary = $progress['summary'];
        $year = $project->start_date?->format('Y') ?? $project->created_at?->format('Y') ?? 'Unknown';
        $startYear = $project->start_date?->format('Y');
        $endYear = $project->end_date?->format('Y');
        $periodLabel = $startYear && $endYear && $endYear !== $startYear
            ? "{$startYear} - {$endYear}"
            : ($startYear ?? $endYear ?? 'Not scheduled');

        return [
            'id' => $project->id,
            'name' => $project->name,
            'status' => $project->status,
            'year' => $year,
            'period_label' => $periodLabel,
            'start_date' => $project->start_date?->format('Y-m-d'),
            'end_date' => $project->end_date?->format('Y-m-d'),
            'description' => $project->description,
            'project_manager_name' => $summary['project_manager_name'],
            'sponsor_name' => $project->sponsor
                ? trim($project->sponsor->organization_name.' - '.$project->sponsor->name)
                : null,
            'total_locations' => (int) $summary['total_locations'],
            'total_beneficiaries' => (int) $summary['total_beneficiaries'],
            'active_beneficiaries' => (int) $summary['active_beneficiaries'],
            'completed_beneficiaries' => (int) $summary['completed_beneficiaries'],
            'dropped_beneficiaries' => (int) $summary['dropped_beneficiaries'],
            'milestone_completion_rate' => (float) $summary['milestone_completion_rate'],
            'beneficiary_completion_rate' => (float) $summary['beneficiary_completion_rate'],
            'attendance_rate' => (float) $summary['attendance_rate'],
            'blocked_locations' => (int) $summary['blocked_locations'],
            'registers_captured' => (int) $summary['registers_captured'],
            'blockers' => $summary['blockers'],
        ];
    }

    protected function summarizeProgram(Program $program): array
    {
        $projectSnapshots = $program->projects
            ->sortBy([
                ['start_date', 'asc'],
                ['created_at', 'asc'],
            ])
            ->values()
            ->map(fn (Project $project) => $this->mapProjectSnapshot($project))
            ->values();

        $beneficiaryIds = $program->projects
            ->flatMap(fn (Project $project) => $project->enrollments->pluck('beneficiary_id'))
            ->filter()
            ->unique()
            ->values();

        $yearlyImpact = $projectSnapshots
            ->groupBy('year')
            ->map(function (Collection $projects, string $year) {
                return [
                    'year' => $year,
                    'projects' => $projects->count(),
                    'beneficiaries' => (int) $projects->sum('total_beneficiaries'),
                    'active_beneficiaries' => (int) $projects->sum('active_beneficiaries'),
                    'locations' => (int) $projects->sum('total_locations'),
                    'completed_projects' => (int) $projects->where('status', 'completed')->count(),
                ];
            })
            ->sortKeys()
            ->values();

        return [
            'program' => $program,
            'stats' => [
                'total_projects' => $projectSnapshots->count(),
                'active_projects' => (int) $projectSnapshots->where('status', 'active')->count(),
                'completed_projects' => (int) $projectSnapshots->where('status', 'completed')->count(),
                'total_locations' => (int) $projectSnapshots->sum('total_locations'),
                'milestone_templates_count' => (int) $program->milestone_templates_count,
                'tracked_beneficiaries' => (int) $projectSnapshots->sum('total_beneficiaries'),
                'unique_beneficiaries' => $beneficiaryIds->count(),
                'active_beneficiaries' => (int) $projectSnapshots->sum('active_beneficiaries'),
                'completed_beneficiaries' => (int) $projectSnapshots->sum('completed_beneficiaries'),
                'dropped_beneficiaries' => (int) $projectSnapshots->sum('dropped_beneficiaries'),
                'blocked_locations' => (int) $projectSnapshots->sum('blocked_locations'),
                'active_years' => $yearlyImpact->count(),
                'average_milestone_completion_rate' => round((float) $projectSnapshots->avg('milestone_completion_rate'), 2),
                'average_beneficiary_completion_rate' => round((float) $projectSnapshots->avg('beneficiary_completion_rate'), 2),
                'average_attendance_rate' => round((float) $projectSnapshots->avg('attendance_rate'), 2),
            ],
            'yearly_impact' => $yearlyImpact->all(),
            'projects' => $projectSnapshots->all(),
        ];
    }
}
