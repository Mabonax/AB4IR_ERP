<?php

namespace App\Domains\Events\Repositories;

use App\Domains\Events\Models\EventSeries;
use Illuminate\Support\Collection;

interface EventSeriesRepositoryInterface
{
    public function allWithSummary(): Collection;

    public function findBySlugOrKey(string $value): ?EventSeries;

    public function create(array $data): EventSeries;

    public function update(EventSeries $series, array $data): EventSeries;
}
