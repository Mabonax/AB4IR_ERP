<?php

namespace App\Domains\HumanCapital\Controllers;

use App\Domains\HumanCapital\Services\HumanCapitalDashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HumanCapitalDashboardController extends Controller
{
    public function __construct(
        protected HumanCapitalDashboardService $service
    ) {}

    public function dashboard(Request $request): Response
    {
        abort_unless(
            $request->user()?->can('domain.human-capital.view')
            || $request->user()?->can('domain.human-capital.manage')
            || $request->user()?->can('domain.members.view')
            || $request->user()?->can('domain.members.manage'),
            403
        );

        return Inertia::render('HumanCapital/Dashboard', $this->service->dashboard());
    }

    public function reports(Request $request): Response
    {
        abort_unless(
            $request->user()?->can('domain.human-capital.view')
            || $request->user()?->can('domain.human-capital.manage')
            || $request->user()?->can('domain.reporting.view')
            || $request->user()?->can('domain.reporting.manage'),
            403
        );

        return Inertia::render('HumanCapital/Reports', $this->service->reports());
    }
}
