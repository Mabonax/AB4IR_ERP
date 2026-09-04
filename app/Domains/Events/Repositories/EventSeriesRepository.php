<?php

namespace App\Domains\Events\Repositories;

use App\Domains\Events\Models\EventSeries;
use Illuminate\Support\Collection;

class EventSeriesRepository implements EventSeriesRepositoryInterface
{
    public function allWithSummary(): Collection
    {
        return EventSeries::query()
            ->with(['events.owner', 'events.participants', 'events.outcomeReport', 'events.closureReport', 'assets.document.folder'])
            ->withCount('events')
            ->orderBy('name')
            ->get();
    }

    public function findBySlugOrKey(string $value): ?EventSeries
    {
        return EventSeries::query()
            ->with(['events.owner', 'events.participants', 'events.partners', 'events.workstreams.tasks.attachments', 'events.workstreams.tasks.submittedBy', 'events.workstreams.tasks.reviewedBy', 'events.outcomeReport.reporter', 'events.closureReport.assets.uploadedBy', 'assets.document.folder'])
            ->where('slug', $value)
            ->orWhere('series_key', $value)
            ->first();
    }

    public function create(array $data): EventSeries
    {
        return EventSeries::query()->create($data);
    }

    public function update(EventSeries $series, array $data): EventSeries
    {
        $series->update($data);

        return $series->fresh(['events.owner', 'events.participants', 'assets.document.folder']);
    }
}
