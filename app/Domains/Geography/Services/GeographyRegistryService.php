<?php

namespace App\Domains\Geography\Services;

use App\Domains\Geography\Interfaces\GeographyRepositoryInterface;

class GeographyRegistryService
{
    public function __construct(
        protected GeographyRepositoryInterface $repository
    ) {}

    public function referenceData(): array
    {
        $data = $this->repository->referenceData();

        return [
            'provinces' => $data['provinces']->map(fn ($province) => [
                'id' => $province->id,
                'name' => $province->name,
            ])->values()->all(),
            'municipalities' => $data['municipalities']->map(fn ($record) => [
                'id' => $record->id,
                'name' => $record->name,
                'code' => $record->code,
                'province_name' => $record->province?->name,
            ])->values()->all(),
            'regions' => $data['regions']->map(fn ($record) => [
                'id' => $record->id,
                'name' => $record->name,
                'code' => $record->code,
                'province_name' => $record->province?->name,
                'municipality_name' => $record->municipality?->name,
            ])->values()->all(),
            'townships' => $data['townships']->map(fn ($record) => [
                'id' => $record->id,
                'name' => $record->name,
                'province_name' => $record->province?->name,
                'municipality_name' => $record->municipality?->name,
                'region_name' => $record->region?->name,
            ])->values()->all(),
            'wards' => $data['wards']->map(fn ($record) => [
                'id' => $record->id,
                'name' => $record->name,
                'code' => $record->code,
                'province_name' => $record->province?->name,
                'municipality_name' => $record->municipality?->name,
                'region_name' => $record->region?->name,
                'township_name' => $record->township?->name,
            ])->values()->all(),
            'branches' => $data['branches']->map(fn ($record) => [
                'id' => $record->id,
                'name' => $record->name,
                'code' => $record->code,
                'province_name' => $record->province?->name,
                'municipality_name' => $record->municipality?->name,
                'region_name' => $record->region?->name,
                'township_name' => $record->township?->name,
                'ward_name' => $record->ward?->name,
            ])->values()->all(),
        ];
    }

    public function createRecord(string $type, array $payload): mixed
    {
        return $this->repository->createRecord($type, collect($payload)
            ->only(['province_id', 'municipality_id', 'region_id', 'township_id', 'ward_id', 'name', 'code'])
            ->all());
    }
}
