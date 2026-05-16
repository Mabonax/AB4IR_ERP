<?php

namespace App\Domains\BusinessDevelopment\Controllers;

use App\Domains\BusinessDevelopment\Models\BdsIncubatee;
use App\Domains\BusinessDevelopment\Models\BdsIncubateeKpi;
use App\Domains\BusinessDevelopment\Policies\BdsIncubateeKpiPolicy;
use App\Domains\BusinessDevelopment\Requests\AssignBdsIncubateeKpiRequest;
use App\Domains\BusinessDevelopment\Requests\StoreBdsIncubateeKpiReviewRequest;
use App\Domains\BusinessDevelopment\Resources\BdsIncubateeKpiResource;
use App\Domains\BusinessDevelopment\Services\BdsIncubateeKpiService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class BdsIncubateeKpiController extends Controller
{
    public function __construct(
        protected BdsIncubateeKpiService $service
    ) {}

    public function assign(AssignBdsIncubateeKpiRequest $request, BdsIncubatee $incubatee): JsonResponse
    {
        Gate::authorize('assign', BdsIncubateeKpiPolicy::class);

        $kpi = $this->service->assignKpi(
            $incubatee,
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'message' => 'KPI assigned successfully.',
            'data' => new BdsIncubateeKpiResource(
                $kpi->load(['definition', 'reviews'])
            ),
        ]);
    }

    public function review(StoreBdsIncubateeKpiReviewRequest $request, BdsIncubateeKpi $kpi): JsonResponse
    {
        Gate::authorize('review', $kpi);

        $this->service->recordReview(
            $kpi,
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'message' => 'KPI review recorded successfully.',
            'data' => new BdsIncubateeKpiResource(
                $kpi->fresh(['definition', 'reviews'])
            ),
        ]);
    }
}
