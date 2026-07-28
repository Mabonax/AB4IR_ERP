<?php

namespace App\Domains\Geography\Repositories;

use App\Domains\Geography\Interfaces\GeographyRepositoryInterface;
use App\Domains\Geography\Models\Branch;
use App\Domains\Geography\Models\Municipality;
use App\Domains\Geography\Models\Region;
use App\Domains\Geography\Models\Township;
use App\Domains\Geography\Models\Ward;
use App\Models\Provinces;

class GeographyRepository implements GeographyRepositoryInterface
{
    public function referenceData(): array
    {
        return [
            'provinces' => Provinces::query()->orderBy('name')->get(),
            'municipalities' => Municipality::query()->with('province')->orderBy('name')->get(),
            'regions' => Region::query()->with(['province', 'municipality'])->orderBy('name')->get(),
            'townships' => Township::query()->with(['province', 'municipality', 'region'])->orderBy('name')->get(),
            'wards' => Ward::query()->with(['province', 'municipality', 'region', 'township'])->orderBy('name')->get(),
            'branches' => Branch::query()->with(['province', 'municipality', 'region', 'township', 'ward'])->orderBy('name')->get(),
        ];
    }

    public function createRecord(string $type, array $payload): mixed
    {
        $modelClass = match ($type) {
            'municipality' => Municipality::class,
            'region' => Region::class,
            'township' => Township::class,
            'ward' => Ward::class,
            'branch' => Branch::class,
            default => throw new \InvalidArgumentException("Unsupported geography type [{$type}]."),
        };

        return $modelClass::query()->create($payload);
    }
}
