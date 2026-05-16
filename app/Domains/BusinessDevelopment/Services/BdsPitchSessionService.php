<?php

namespace App\Domains\BusinessDevelopment\Services;

use App\Domains\BusinessDevelopment\Adjudication\Models\AdjudicationAssessment;
use App\Domains\BusinessDevelopment\Models\BdsApplication;
use App\Domains\BusinessDevelopment\Models\BdsIncubatee;
use App\Domains\BusinessDevelopment\Models\BdsPitchSession;
use App\Domains\BusinessDevelopment\Models\BdsPitchSessionProspect;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BdsPitchSessionService
{
    public function paginate(int $perPage, ?User $actor = null): LengthAwarePaginator
    {
        $query = BdsPitchSession::query()
            ->with([
                'creator:id,name',
                'approver:id,name',
                'panelists.user:id,name,email',
                'prospects.application:id,full_name,company_name,assessment_status,adjudication_result',
                'assessments.judge:id,name',
            ])
            ->latest('scheduled_for');

        if ($actor && ! $this->hasWorkflowRole($actor)) {
            $query->whereHas('panelists', fn ($panelists) => $panelists->where('user_id', $actor->id));
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getById(int $id): BdsPitchSession
    {
        return BdsPitchSession::query()
            ->with([
                'creator:id,name',
                'approver:id,name',
                'panelists.user:id,name,email',
                'prospects.application:id,full_name,company_name,assessment_status,adjudication_result',
                'assessments.judge:id,name',
            ])
            ->findOrFail($id);
    }

    public function createSession(array $data, User $actor): BdsPitchSession
    {
        $this->assertBusinessDevelopmentManager($actor);

        return DB::transaction(function () use ($data, $actor) {
            $panelists = collect($data['panelists'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
            $prospects = collect($data['prospects'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();

            if ($panelists->count() < 2) {
                throw ValidationException::withMessages([
                    'panelists' => ['A pitch session requires at least two panel members.'],
                ]);
            }

            if ($prospects->isEmpty()) {
                throw ValidationException::withMessages([
                    'prospects' => ['A pitch session must include at least one prospect.'],
                ]);
            }

            $scheduledFor = Carbon::parse($data['scheduled_for']);
            $applications = BdsApplication::query()
                ->whereIn('id', $prospects->all())
                ->withExists([
                    'adjudications as has_submitted_adjudication' => fn ($query) => $query->where('status', 'submitted'),
                ])
                ->get()
                ->keyBy('id');

            foreach ($prospects as $prospectId) {
                $application = $applications->get($prospectId);
                if (! $application) {
                    throw ValidationException::withMessages([
                        'prospects' => ["Selected prospect {$prospectId} could not be found."],
                    ]);
                }

                $this->assertApplicationReadyForPitchSession($application);
            }

            $session = BdsPitchSession::query()->create([
                'title' => $data['title'],
                'scheduled_for' => $scheduledFor,
                'venue' => $data['venue'] ?? null,
                'expected_prospect_count' => $data['expected_prospect_count'] ?? $prospects->count(),
                'notes' => $data['notes'] ?? null,
                'status' => 'scheduled',
                'created_by' => $actor->id,
            ]);

            foreach ($panelists->values() as $index => $panelistId) {
                $session->panelists()->create([
                    'user_id' => $panelistId,
                    'panel_role' => $index === 0 ? 'bds' : 'technical',
                    'is_chair' => $index === 0,
                ]);
            }

            foreach ($prospects->values() as $index => $prospectId) {
                $session->prospects()->create([
                    'bds_application_id' => $prospectId,
                    'sequence_number' => $index + 1,
                ]);

                $applications[$prospectId]->update([
                    'pitch_scheduled_at' => $scheduledFor,
                    'pitch_notes' => $data['notes'] ?? $applications[$prospectId]->pitch_notes,
                    'updated_by' => $actor->id,
                ]);
            }

            return $session->load(['panelists.user', 'prospects.application']);
        });
    }

    public function startSession(BdsPitchSession $session, User $actor): BdsPitchSession
    {
        $this->assertBusinessDevelopmentManager($actor);

        if ($session->status !== 'scheduled') {
            throw ValidationException::withMessages([
                'status' => ['Only scheduled pitch sessions can be started.'],
            ]);
        }

        if ($session->panelists()->count() < 2) {
            throw ValidationException::withMessages([
                'panelists' => ['A pitch session requires at least two panel members before it can start.'],
            ]);
        }

        if (! $session->prospects()->exists()) {
            throw ValidationException::withMessages([
                'prospects' => ['A pitch session needs listed prospects before it can start.'],
            ]);
        }

        $session->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return $session->fresh(['panelists.user', 'prospects.application']);
    }

    public function consolidateProspect(BdsPitchSessionProspect $prospect, User $actor): BdsPitchSessionProspect
    {
        $this->assertBusinessDevelopmentManager($actor);

        $session = $prospect->session()->firstOrFail();
        if (! in_array($session->status, ['in_progress', 'consolidated', 'approved'], true)) {
            throw ValidationException::withMessages([
                'session' => ['Only active or completed pitch sessions can be consolidated.'],
            ]);
        }

        $submittedAssessments = AdjudicationAssessment::query()
            ->where('pitch_session_id', $session->id)
            ->where('smme_id', $prospect->bds_application_id)
            ->where('status', 'submitted')
            ->get();

        if ($submittedAssessments->count() < 2) {
            throw ValidationException::withMessages([
                'assessments' => ['At least two submitted panel assessments are required before consolidation.'],
            ]);
        }

        $prospect->update([
            'consolidated_total_score' => (int) $submittedAssessments->sum('total_score'),
            'submitted_assessments_count' => $submittedAssessments->count(),
        ]);

        if ($session->status === 'in_progress') {
            $session->update([
                'status' => 'consolidated',
                'consolidated_at' => now(),
            ]);
        }

        return $prospect->fresh(['application', 'session']);
    }

    public function approveProspect(BdsPitchSessionProspect $prospect, User $actor, string $decision, ?string $notes = null): BdsPitchSessionProspect
    {
        $this->assertBusinessDevelopmentManager($actor);

        if (! in_array($decision, ['incubated', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'manager_decision' => ['Manager decision must be incubated or rejected.'],
            ]);
        }

        if ((int) $prospect->submitted_assessments_count < 2) {
            throw ValidationException::withMessages([
                'manager_decision' => ['The panel outcome must be consolidated before final approval.'],
            ]);
        }

        return DB::transaction(function () use ($prospect, $actor, $decision, $notes) {
            $prospect->update([
                'manager_decision' => $decision,
                'manager_decided_at' => now(),
                'manager_notes' => $notes,
            ]);

            $application = $prospect->application()->firstOrFail();
            $application->update([
                'adjudication_result' => $decision,
                'adjudicated_at' => now(),
                'updated_by' => $actor->id,
            ]);

            if ($decision === 'incubated') {
                $this->upsertIncubateeFromApplication($application, $actor->id);
            } else {
                BdsIncubatee::query()
                    ->where('bds_application_id', $application->id)
                    ->update([
                        'status' => 'inactive',
                        'updated_by' => $actor->id,
                        'updated_at' => now(),
                    ]);
            }

            $session = $prospect->session()->firstOrFail();
            $allDecided = $session->prospects()->whereNull('manager_decision')->doesntExist();
            if ($allDecided) {
                $session->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'approved_by' => $actor->id,
                ]);
            }

            return $prospect->fresh(['application', 'session']);
        });
    }

    protected function assertBusinessDevelopmentManager(User $actor): void
    {
        $hasPermission = $actor->can('domain.business-development.manage');
        $hasRole = $this->hasWorkflowRole($actor);

        if (! $hasPermission || ! $hasRole) {
            throw ValidationException::withMessages([
                'authorization' => ['You are not authorized to manage pitch sessions.'],
            ]);
        }
    }

    protected function hasWorkflowRole(User $actor): bool
    {
        return method_exists($actor, 'hasAnyRole') && $actor->hasAnyRole([
            'super-admin',
            'super admin',
            'admin',
            'domain-admin-business-development',
            'department-manager-business-development',
        ]);
    }

    protected function assertApplicationReadyForPitchSession(BdsApplication $application): void
    {
        if ($application->assessment_status !== 'accepted') {
            throw ValidationException::withMessages([
                'prospects' => ["{$application->company_name} must be accepted before it can be added to a pitch session."],
            ]);
        }

        if ($application->adjudication_result !== null) {
            throw ValidationException::withMessages([
                'prospects' => ["{$application->company_name} already has a final adjudication result."],
            ]);
        }

        if ((bool) ($application->has_submitted_adjudication ?? false)) {
            throw ValidationException::withMessages([
                'prospects' => ["{$application->company_name} already has a submitted adjudication panel scorecard."],
            ]);
        }
    }

    protected function upsertIncubateeFromApplication(BdsApplication $application, int $actorId): void
    {
        $payload = [
            'bds_application_id' => (int) $application->id,
            'full_name' => $application->full_name,
            'id_number' => $application->id_number,
            'gender' => $application->gender,
            'mobile_number' => $application->mobile_number,
            'email' => $application->email,
            'company_name' => $application->company_name,
            'company_registration_number' => $application->company_registration_number,
            'position_in_company' => $application->position_in_company,
            'majority_shareholding' => $application->majority_shareholding,
            'current_number_of_employees' => $application->current_number_of_employees,
            'physical_address' => $application->physical_address,
            'website_address' => $application->website_address,
            'years_in_operation' => $application->years_in_operation,
            'province_id' => $application->province_id,
            'has_business_plan' => (bool) $application->has_business_plan,
            'relevant_skill_set' => $application->relevant_skill_set,
            'technology_product_service' => $application->technology_product_service,
            'technology_stage_of_development' => $application->technology_stage_of_development,
            'status' => 'active',
            'incubated_date' => now()->toDateString(),
            'updated_by' => $actorId,
        ];

        $incubatee = BdsIncubatee::query()
            ->where('bds_application_id', $application->id)
            ->orWhere('id_number', $application->id_number)
            ->orWhere('company_registration_number', $application->company_registration_number)
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
}
