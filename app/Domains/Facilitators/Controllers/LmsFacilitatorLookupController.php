<?php

namespace App\Domains\Facilitators\Controllers;

use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Projects\Models\ProjectLocation;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LmsFacilitatorLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorizeBridgeOrUser($request);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:25'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 10);

        $facilitators = Facilitator::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('cell', 'like', "%{$search}%")
                        ->orWhere('specialization', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->orderBy('surname')
            ->limit($limit)
            ->get();

        $assignments = ProjectLocation::query()
            ->with(['project.program', 'province'])
            ->whereIn('facilitator_id', $facilitators->pluck('id'))
            ->get()
            ->groupBy('facilitator_id');

        return response()->json([
            'data' => $facilitators->map(fn (Facilitator $facilitator): array => $this->mapFacilitator(
                $facilitator,
                $assignments->get($facilitator->id, collect())
            ))->values(),
        ]);
    }

    private function authorizeBridgeOrUser(Request $request): void
    {
        $configuredToken = (string) config('services.lms_bridge.token');
        $providedToken = (string) $request->header('X-LMS-BRIDGE-TOKEN');

        if ($configuredToken !== '' && hash_equals($configuredToken, $providedToken)) {
            return;
        }

        if ($request->user()) {
            Gate::forUser($request->user())->authorize('viewAny', Facilitator::class);

            return;
        }

        abort(response()->json([
            'message' => 'Unauthorized LMS bridge request.',
        ], 403));
    }

    private function mapFacilitator(Facilitator $facilitator, $locations): array
    {
        return [
            'erp_facilitator_id' => (string) $facilitator->id,
            'name' => trim("{$facilitator->name} {$facilitator->surname}"),
            'email' => $facilitator->email,
            'phone' => $facilitator->cell,
            'status' => 'active',
            'specialization' => $facilitator->specialization,
            'assignments' => $locations->map(fn (ProjectLocation $location): array => [
                'erp_project_location_id' => (string) $location->id,
                'project' => $location->project ? [
                    'id' => $location->project->id,
                    'name' => $location->project->name,
                    'status' => $location->project->status,
                ] : null,
                'programme' => $location->project?->program ? [
                    'id' => $location->project->program->id,
                    'title' => $location->project->program->title,
                ] : null,
                'location' => [
                    'id' => $location->id,
                    'name' => $location->training_venue_address,
                    'province' => $location->province?->name,
                ],
            ])->values(),
            'synced_at' => now()->toIso8601String(),
        ];
    }
}
