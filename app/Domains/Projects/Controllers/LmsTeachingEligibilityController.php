<?php

namespace App\Domains\Projects\Controllers;

use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\ProjectLearningDeliveryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LmsTeachingEligibilityController extends Controller
{
    public function __construct(private readonly ProjectLearningDeliveryService $delivery)
    {
    }

    public function __invoke(Request $request, int $project, int $facilitator): JsonResponse
    {
        $this->authorizeBridge($request);

        $projectModel = Project::query()->findOrFail($project);
        $facilitatorModel = Facilitator::query()->findOrFail($facilitator);

        return response()->json($this->delivery->facilitatorEligibility($projectModel, $facilitatorModel));
    }

    private function authorizeBridge(Request $request): void
    {
        $configuredToken = (string) config('services.lms_bridge.token');
        $providedToken = (string) $request->header('X-LMS-BRIDGE-TOKEN');

        if ($configuredToken !== '' && hash_equals($configuredToken, $providedToken)) {
            return;
        }

        abort(response()->json(['message' => 'Unauthorized LMS bridge request.'], 403));
    }
}
