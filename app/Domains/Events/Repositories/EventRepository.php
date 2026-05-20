<?php

namespace App\Domains\Events\Repositories;

use App\Domains\Events\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EventRepository implements EventRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Event::query()
            ->with(['owner', 'participants', 'partners', 'workstreams.tasks', 'outcomeReport.reporter'])
            ->orderByDesc('start_date')
            ->paginate($perPage);
    }

    public function find(int $id): ?Event
    {
        return Event::query()
            ->with(['owner', 'participants', 'partners', 'workstreams.tasks', 'outcomeReport.reporter'])
            ->find($id);
    }

    public function create(array $data): Event
    {
        return Event::query()->create($data);
    }

    public function update(Event $event, array $data): Event
    {
        $event->update($data);

        return $event->fresh(['owner', 'participants', 'partners', 'workstreams.tasks', 'outcomeReport.reporter']);
    }

    public function delete(Event $event): bool
    {
        return $event->delete();
    }

    public function seriesHistory(string $seriesKey): Collection
    {
        return Event::query()
            ->with(['owner', 'participants', 'partners', 'workstreams.tasks', 'outcomeReport.reporter'])
            ->where('annual_series_key', $seriesKey)
            ->orderByDesc('event_year')
            ->orderByDesc('start_date')
            ->get();
    }
}
