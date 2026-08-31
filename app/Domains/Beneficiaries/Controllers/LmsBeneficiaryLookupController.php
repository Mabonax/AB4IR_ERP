<?php

namespace App\Domains\Beneficiaries\Controllers;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Http\Controllers\Controller;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LmsBeneficiaryLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorizeBridgeRequest($request);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:25'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 10);

        $beneficiaries = Beneficiary::query()
            ->with([
                'project.program',
                'projectEnrollments.project.program',
                'projectEnrollments.location.province',
            ])
            ->whereNull('deleted_at')
            ->whereIn('status', Beneficiary::ACTIVE_LIFECYCLE_STATUSES)
            ->where(fn ($query) => $query
                ->whereNull('attendance_status')
                ->orWhere('attendance_status', '!=', 'dropout'))
            ->whereHas('projectEnrollments', fn ($query) => $query->where('status', 'enrolled'))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('id_number', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Beneficiary $beneficiary): array => $this->mapBeneficiary($beneficiary))
            ->values();

        return response()->json([
            'data' => $beneficiaries,
        ]);
    }

    private function authorizeBridgeRequest(Request $request): void
    {
        if ($request->user()) {
            Gate::forUser($request->user())->authorize('viewAny', Beneficiary::class);

            return;
        }

        $configuredToken = (string) config('services.lms_bridge.token');
        $providedToken = (string) $request->header('X-LMS-BRIDGE-TOKEN');

        if ($configuredToken !== '' && hash_equals($configuredToken, $providedToken)) {
            return;
        }

        throw new HttpResponseException(response()->json([
            'message' => 'Unauthorized LMS bridge request.',
        ], 403));
    }

    private function mapBeneficiary(Beneficiary $beneficiary): array
    {
        $currentEnrollment = $beneficiary->projectEnrollments
            ->where('status', 'enrolled')
            ->sortByDesc(fn ($enrollment) => optional($enrollment->enrolled_at)?->timestamp ?? 0)
            ->first();

        $project = $currentEnrollment?->project ?? $beneficiary->project;

        return [
            'erp_beneficiary_id' => (string) $beneficiary->id,
            'erp_project_enrollment_id' => $currentEnrollment ? (string) $currentEnrollment->id : null,
            'name' => trim(($beneficiary->name ?? '').' '.($beneficiary->surname ?? '')),
            'email' => $beneficiary->email,
            'phone' => $beneficiary->phone,
            'id_number' => $beneficiary->id_number,
            'status' => $beneficiary->status ?? 'enrolled',
            'attendance_status' => $beneficiary->attendance_status ?? 'active',
            'project' => $project ? [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
            ] : null,
            'programme' => $project?->program ? [
                'id' => $project->program->id,
                'title' => $project->program->title,
            ] : null,
            'location' => $currentEnrollment?->location ? [
                'id' => $currentEnrollment->project_location_id,
                'name' => $currentEnrollment->location->province?->name,
            ] : null,
            'synced_at' => now()->toISOString(),
        ];
    }
}
