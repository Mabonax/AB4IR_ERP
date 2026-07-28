<?php

namespace App\Domains\Geography\Interfaces;

interface GeographyRepositoryInterface
{
    public function referenceData(): array;

    public function createRecord(string $type, array $payload): mixed;
}
