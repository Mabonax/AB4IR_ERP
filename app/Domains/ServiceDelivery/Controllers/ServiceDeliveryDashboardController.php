<?php

namespace App\Domains\ServiceDelivery\Controllers;

use App\Domains\ServiceDelivery\Services\ServiceDeliveryDashboardService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ServiceDeliveryDashboardController extends Controller
{
    public function __construct(
        protected ServiceDeliveryDashboardService $service,
    ) {}

    public function __invoke(): Response
    {
        return Inertia::render('ServiceDelivery/Dashboard', [
            'dashboard' => $this->service->dashboard(),
        ]);
    }
}
