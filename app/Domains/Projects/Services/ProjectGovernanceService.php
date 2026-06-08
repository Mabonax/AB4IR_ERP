<?php

namespace App\Domains\Projects\Services;

use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectClosure;
use App\Domains\Projects\Models\ProjectClosureEvidence;
use App\Domains\Projects\Models\ProjectReport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProjectGovernanceService
{
    public function __construct(
        protected ProjectService $projectService,
        protected ProjectProgressService $progressService,
        protected ProjectHistoryService $historyService
    ) {}

    public function concludeProject(Project $project, array $data, User $user): ProjectClosure
    {
        if ($project->closure()->exists()) {
            throw ValidationException::withMessages([
                'closure_date' => ['This project has already been concluded.'],
            ]);
        }

        return DB::transaction(function () use ($project, $data, $user) {
            $updatedProject = $this->projectService->updateProject($project->id, [
                'program_id' => $project->program_id,
                'sponsor_stakeholder_id' => $project->sponsor_stakeholder_id,
                'partner_stakeholder_ids' => $project->partners()->pluck('stakeholders.id')->all(),
                'project_manager_id' => $project->project_manager_id,
                'contract_reference' => $project->contract_reference,
                'funding_amount' => $project->funding_amount,
                'reporting_cadence' => $project->reporting_cadence,
                'reporting_obligations' => $project->reporting_obligations,
                'name' => $project->name,
                'start_date' => $project->start_date?->format('Y-m-d'),
                'end_date' => $data['closure_date'],
                'status' => 'completed',
                'description' => $project->description,
            ], $user);

            $snapshot = $this->progressService->summarizeProject($updatedProject->fresh());

            $closure = ProjectClosure::query()->create([
                'project_id' => $updatedProject->id,
                'closure_date' => $data['closure_date'],
                'requested_by_user_id' => $user->id,
                'concluded_by_user_id' => $user->id,
                'signoff_notes' => $data['signoff_notes'] ?? null,
                'final_report_summary' => $data['final_report_summary'] ?? null,
                'snapshot' => $snapshot,
            ]);

            $report = $this->createReport($updatedProject->fresh(), [
                'report_type' => 'final',
                'title' => $data['report_title'] ?? "{$updatedProject->name} Final Report",
                'report_date' => $data['closure_date'],
                'executive_summary' => $data['final_report_summary'] ?? null,
                'key_findings' => $data['key_findings'] ?? null,
                'recommendations' => $data['recommendations'] ?? null,
                'project_closure_id' => $closure->id,
            ], $user);

            $this->historyService->record(
                $updatedProject,
                'concluded',
                'Project concluded.',
                $user,
                [
                    'closure_date' => $data['closure_date'],
                    'project_closure_id' => $closure->id,
                    'project_report_id' => $report->id,
                ]
            );

            return $closure->fresh(['project', 'requestedBy', 'concludedBy']);
        });
    }

    public function createReport(Project $project, array $data, User $user): ProjectReport
    {
        $reportType = (string) ($data['report_type'] ?? 'progress');

        if ($reportType === 'final' && $project->status !== 'completed') {
            throw ValidationException::withMessages([
                'report_type' => ['A final report can only be created once the project is completed.'],
            ]);
        }

        return DB::transaction(function () use ($project, $data, $user, $reportType) {
            $snapshot = $this->progressService->summarizeProject($project->fresh());

            $report = ProjectReport::query()->create([
                'project_id' => $project->id,
                'project_closure_id' => $data['project_closure_id'] ?? $project->closure?->id,
                'report_type' => $reportType,
                'title' => $data['title'] ?? $this->defaultReportTitle($project, $reportType, $data['report_date']),
                'report_date' => $data['report_date'],
                'executive_summary' => $data['executive_summary'] ?? null,
                'key_findings' => $data['key_findings'] ?? null,
                'recommendations' => $data['recommendations'] ?? null,
                'snapshot' => $snapshot,
                'created_by_user_id' => $user->id,
            ]);

            $this->historyService->record(
                $project,
                'report_created',
                ucfirst($reportType).' project report created.',
                $user,
                [
                    'project_report_id' => $report->id,
                    'report_type' => $reportType,
                    'report_date' => $data['report_date'],
                ]
            );

            return $report;
        });
    }

    public function uploadClosureEvidence(Project $project, array $data, UploadedFile $file, User $user): ProjectClosureEvidence
    {
        return DB::transaction(function () use ($project, $data, $file, $user) {
            $path = $file->store("project-closure-evidence/{$project->id}", 'local');

            $evidence = ProjectClosureEvidence::query()->create([
                'project_id' => $project->id,
                'project_closure_id' => $project->closure?->id,
                'category' => $data['category'] ?? 'evidence',
                'title' => $data['title'],
                'file_name' => $file->getClientOriginalName(),
                'disk' => 'local',
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'notes' => $data['notes'] ?? null,
                'uploaded_by_user_id' => $user->id,
            ]);

            $this->historyService->record(
                $project,
                'closure_evidence_uploaded',
                'Closure evidence uploaded.',
                $user,
                [
                    'evidence_id' => $evidence->id,
                    'title' => $evidence->title,
                ]
            );

            return $evidence;
        });
    }

    public function deleteClosureEvidence(Project $project, ProjectClosureEvidence $evidence, User $user): void
    {
        DB::transaction(function () use ($project, $evidence, $user) {
            Storage::disk($evidence->disk)->delete($evidence->path);
            $title = $evidence->title;
            $id = $evidence->id;
            $evidence->delete();

            $this->historyService->record(
                $project,
                'closure_evidence_deleted',
                'Closure evidence removed.',
                $user,
                [
                    'evidence_id' => $id,
                    'title' => $title,
                ]
            );
        });
    }

    public function mapClosure(?ProjectClosure $closure): ?array
    {
        if (! $closure) {
            return null;
        }

        return [
            'id' => $closure->id,
            'closure_date' => $closure->closure_date?->format('Y-m-d'),
            'signoff_notes' => $closure->signoff_notes,
            'final_report_summary' => $closure->final_report_summary,
            'requested_by_name' => $closure->requestedBy?->name,
            'concluded_by_name' => $closure->concludedBy?->name,
            'snapshot' => $closure->snapshot,
        ];
    }

    public function mapReport(ProjectReport $report): array
    {
        return [
            'id' => $report->id,
            'project_id' => $report->project_id,
            'project_closure_id' => $report->project_closure_id,
            'report_type' => $report->report_type,
            'title' => $report->title,
            'report_date' => $report->report_date?->format('Y-m-d'),
            'executive_summary' => $report->executive_summary,
            'key_findings' => $report->key_findings,
            'recommendations' => $report->recommendations,
            'created_by_name' => $report->createdBy?->name,
            'snapshot' => $report->snapshot,
        ];
    }

    public function mapEvidence(ProjectClosureEvidence $evidence): array
    {
        return [
            'id' => $evidence->id,
            'category' => $evidence->category,
            'title' => $evidence->title,
            'file_name' => $evidence->file_name,
            'mime_type' => $evidence->mime_type,
            'file_size' => $evidence->file_size,
            'notes' => $evidence->notes,
            'uploaded_by_name' => $evidence->uploadedBy?->name,
            'created_at' => $evidence->created_at?->toDateTimeString(),
        ];
    }

    protected function defaultReportTitle(Project $project, string $reportType, string $reportDate): string
    {
        $label = $reportType === 'final' ? 'Final Report' : 'Progress Report';

        return "{$project->name} {$label} - {$reportDate}";
    }
}
