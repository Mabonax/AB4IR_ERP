<?php

namespace App\Domains\BusinessDevelopment\Controllers;

use App\Domains\BusinessDevelopment\Models\BdsIncubatee;
use App\Domains\BusinessDevelopment\Models\BdsIncubateeKpi;
use App\Domains\BusinessDevelopment\Policies\BdsIncubateeKpiPolicy;
use App\Domains\BusinessDevelopment\Requests\AssignBdsIncubateeKpiRequest;
use App\Domains\BusinessDevelopment\Requests\StoreBdsIncubateeKpiReviewRequest;
use App\Domains\BusinessDevelopment\Services\BdsIncubateeKpiService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class BdsIncubateeKpiController extends Controller
{
    public function __construct(
        protected BdsIncubateeKpiService $service
    ) {}

    public function assign(AssignBdsIncubateeKpiRequest $request, BdsIncubatee $incubatee): RedirectResponse
    {
        Gate::authorize('assign', BdsIncubateeKpiPolicy::class);

        $this->service->assignKpi(
            $incubatee,
            $request->validated(),
            $request->user()
        );

        return redirect()->back()->with('success', 'KPI assigned successfully.');
    }

    public function review(StoreBdsIncubateeKpiReviewRequest $request, BdsIncubateeKpi $kpi): RedirectResponse
    {
        Gate::authorize('review', $kpi);

        $this->service->recordReview(
            $kpi,
            $request->validated(),
            $request->user()
        );

        return redirect()->back()->with('success', 'KPI review recorded successfully.');
    }
}
