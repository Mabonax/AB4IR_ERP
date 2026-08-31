<?php

namespace App\Domains\Projects\Controllers;

use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\LmsLearningDeliveryClient;
use App\Domains\Projects\Services\ProjectLearningDeliveryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectLearningDeliveryController extends Controller
{
    public function __construct(
        private readonly ProjectLearningDeliveryService $delivery,
        private readonly LmsLearningDeliveryClient $lms,
    ) {
    }

    public function offerings(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        return response()->json([
            'data' => $this->lms->offerings(),
        ]);
    }

    public function map(Request $request, int $project): JsonResponse
    {
        $projectModel = Project::query()->findOrFail($project);
        $this->authorize('update', $projectModel);

        $validated = $request->validate([
            'lms_offering_id' => ['required', 'integer'],
        ]);

        $result = $this->delivery->mapOffering($projectModel, (int) $validated['lms_offering_id'], $request->user());

        return response()->json($result, ($result['status'] ?? null) === 'rejected' ? 422 : 200);
    }

    public function learners(Request $request, int $project): JsonResponse
    {
        $projectModel = Project::query()->findOrFail($project);
        $this->authorize('update', $projectModel);

        $validated = $request->validate([
            'beneficiary_ids' => ['required', 'array', 'min:1'],
            'beneficiary_ids.*' => ['integer'],
        ]);

        $result = $this->delivery->provisionLearners($projectModel, $validated['beneficiary_ids'], $request->user());

        return response()->json($result, ($result['status'] ?? null) === 'rejected' ? 422 : 200);
    }

    public function facilitators(Request $request, int $project): JsonResponse
    {
        $projectModel = Project::query()->findOrFail($project);
        $this->authorize('update', $projectModel);

        $validated = $request->validate([
            'facilitator_ids' => ['required', 'array', 'min:1'],
            'facilitator_ids.*' => ['integer'],
        ]);

        $result = $this->delivery->provisionFacilitators($projectModel, $validated['facilitator_ids'], $request->user());

        return response()->json($result, ($result['status'] ?? null) === 'rejected' ? 422 : 200);
    }

    public function assignFacilitator(Request $request, int $project): JsonResponse
    {
        $projectModel = Project::query()->findOrFail($project);
        $this->authorize('update', $projectModel);

        $validated = $request->validate([
            'facilitator_id' => ['required', 'integer'],
        ]);

        $facilitator = Facilitator::query()->findOrFail($validated['facilitator_id']);

        $result = $this->delivery->assignFacilitator($projectModel, $facilitator, $request->user());

        return response()->json($result, ($result['status'] ?? null) === 'rejected' ? 422 : 200);
    }
}
