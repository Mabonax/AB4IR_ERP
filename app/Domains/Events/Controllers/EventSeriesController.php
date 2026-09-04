<?php

namespace App\Domains\Events\Controllers;

use App\Domains\Events\Models\EventSeries;
use App\Domains\Events\Requests\CreateEventIterationRequest;
use App\Domains\Events\Requests\StoreEventSeriesAssetRequest;
use App\Domains\Events\Requests\StoreEventSeriesRequest;
use App\Domains\Events\Services\EventSeriesService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class EventSeriesController extends Controller
{
    public function __construct(
        protected EventSeriesService $service,
    ) {}

    public function create()
    {
        $this->authorize('create', EventSeries::class);

        return Inertia::render('Events/Series/Create');
    }

    public function store(StoreEventSeriesRequest $request)
    {
        $series = $this->service->createSeries($request->validated(), $request->user());

        return redirect()->route('events.series.show', $series->slug)->with('success', 'Event line created.');
    }

    public function show(EventSeries $eventSeries)
    {
        $this->authorize('view', $eventSeries);
        $this->service->createDefaultSeriesFolders($eventSeries, request()->user());

        return Inertia::render('Events/Series', [
            'series' => $this->service->seriesOverview($eventSeries),
        ]);
    }

    public function createIteration(EventSeries $eventSeries)
    {
        $this->authorize('createIteration', $eventSeries);

        $payload = $this->service->seriesOverview($eventSeries);

        return Inertia::render('Events/Series/CreateIteration', [
            'series' => $payload,
        ]);
    }

    public function storeIteration(CreateEventIterationRequest $request, EventSeries $eventSeries)
    {
        $this->authorize('createIteration', $eventSeries);

        $event = $this->service->createIterationFromPrevious($eventSeries, $request->validated(), $request->user());

        return redirect()->route('events.show', $event->id)->with('success', 'Event iteration created.');
    }

    public function storeAsset(StoreEventSeriesAssetRequest $request, EventSeries $eventSeries)
    {
        $this->authorize('update', $eventSeries);
        $this->service->classifyAsset($eventSeries, $request->validated(), $request->user());

        return redirect()->back()->with('success', 'Series asset classified.');
    }
}
