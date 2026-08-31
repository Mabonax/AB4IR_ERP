<?php

namespace App\Domains\Projects\Services;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectLearningMapping;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class LmsLearningDeliveryClient
{
    public function offerings(): array
    {
        return $this->get('/integrations/erp/learning-offerings')['data'] ?? [];
    }

    public function projectSummary(Project $project): array
    {
        return $this->get("/integrations/erp/projects/{$project->id}/learning-summary")['data'] ?? [
            'integration_state' => 'unavailable',
            'message' => 'LMS summary did not return data.',
        ];
    }

    public function beneficiarySummary(Beneficiary $beneficiary): array
    {
        return $this->get("/integrations/erp/beneficiaries/{$beneficiary->id}/learning-summary")['data'] ?? [
            'lms_access' => 'unavailable',
        ];
    }

    public function facilitatorSummary(Facilitator $facilitator): array
    {
        return $this->get("/integrations/erp/facilitators/{$facilitator->id}/learning-summary")['data'] ?? [
            'lms_access' => 'unavailable',
        ];
    }

    public function mapProject(Project $project, int $cohortId, User $actor): array
    {
        return $this->post('/integrations/erp/project-mappings', [
            'erp_project_id' => (string) $project->id,
            'cohort_id' => $cohortId,
            'mapped_by' => $actor->email,
        ]);
    }

    public function provisionLearner(Project $project, Beneficiary $beneficiary, ProjectLearningMapping $mapping): array
    {
        $enrollment = $beneficiary->projectEnrollments()
            ->where('project_id', $project->id)
            ->latest('enrolled_at')
            ->first();

        return $this->post('/integrations/erp/provisioning/learners', [
            'erp_project_id' => (string) $project->id,
            'cohort_id' => (int) $mapping->lms_offering_id,
            'erp_beneficiary_id' => (string) $beneficiary->id,
            'erp_project_enrollment_id' => $enrollment ? (string) $enrollment->id : null,
            'name' => $beneficiary->full_name,
            'email' => $beneficiary->email,
            'role' => 'Learner',
        ]);
    }

    public function provisionFacilitator(Project $project, Facilitator $facilitator, ProjectLearningMapping $mapping): array
    {
        return $this->post('/integrations/erp/provisioning/facilitators', [
            'erp_project_id' => (string) $project->id,
            'cohort_id' => (int) $mapping->lms_offering_id,
            'erp_facilitator_id' => (string) $facilitator->id,
            'name' => trim("{$facilitator->name} {$facilitator->surname}"),
            'email' => $facilitator->email,
        ]);
    }

    public function assignFacilitator(Project $project, Facilitator $facilitator, ProjectLearningMapping $mapping): array
    {
        return $this->post('/integrations/erp/teaching-assignments', [
            'erp_project_id' => (string) $project->id,
            'cohort_id' => (int) $mapping->lms_offering_id,
            'erp_facilitator_id' => (string) $facilitator->id,
        ]);
    }

    public function resendBeneficiaryInvitation(Beneficiary $beneficiary): array
    {
        return $this->post('/integrations/erp/invitations/resend', [
            'identity_type' => 'beneficiary',
            'erp_identity_id' => (string) $beneficiary->id,
        ]);
    }

    public function resendFacilitatorInvitation(Facilitator $facilitator): array
    {
        return $this->post('/integrations/erp/invitations/resend', [
            'identity_type' => 'facilitator',
            'erp_identity_id' => (string) $facilitator->id,
        ]);
    }

    public function applyBeneficiaryLifecycle(Beneficiary $beneficiary, string $action): array
    {
        return $this->post('/integrations/erp/access-lifecycle', [
            'identity_type' => 'beneficiary',
            'erp_identity_id' => (string) $beneficiary->id,
            'action' => $action,
        ]);
    }

    public function applyFacilitatorLifecycle(Facilitator $facilitator, string $action): array
    {
        return $this->post('/integrations/erp/access-lifecycle', [
            'identity_type' => 'facilitator',
            'erp_identity_id' => (string) $facilitator->id,
            'action' => $action,
        ]);
    }

    private function get(string $path): array
    {
        return $this->request('get', $path);
    }

    private function post(string $path, array $payload): array
    {
        return $this->request('post', $path, $payload);
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $baseUrl = rtrim((string) config('services.lms.app_url'), '/');
        $token = (string) config('services.lms_bridge.token');

        if ($baseUrl === '' || $token === '') {
            return [
                'status' => 'unavailable',
                'integration_state' => 'unavailable',
                'reason' => 'LMS integration is not configured.',
            ];
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['X-LMS-BRIDGE-TOKEN' => $token])
                ->timeout(10)
                ->{$method}($baseUrl.$path, $payload);
        } catch (ConnectionException) {
            return [
                'status' => 'unavailable',
                'integration_state' => 'unavailable',
                'reason' => 'LMS could not be reached.',
            ];
        }

        if (! $response->successful()) {
            return [
                'status' => 'rejected',
                'integration_state' => 'error',
                'reason' => $response->json('reason')
                    ?? $response->json('message')
                    ?? 'LMS bridge request failed.',
                'http_status' => $response->status(),
            ];
        }

        return $response->json() ?: [
            'status' => $response->successful() ? 'ok' : 'error',
            'reason' => $response->body() ?: 'LMS returned an invalid response.',
        ];
    }
}
