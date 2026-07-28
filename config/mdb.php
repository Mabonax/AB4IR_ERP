<?php

use App\Domains\Geography\Services\MdbGeographyDownloader;

$csvToArray = static function (?string $value): array {
    return collect(explode(',', (string) $value))
        ->map(static fn (string $entry): string => trim($entry))
        ->filter()
        ->values()
        ->all();
};

return [
    'http' => [
        'page_size' => (int) env('MDB_HTTP_PAGE_SIZE', 100),
        'timeout_seconds' => (int) env('MDB_HTTP_TIMEOUT_SECONDS', 300),
    ],
    'sources' => [
        'local_municipalities' => array_values(array_filter([
            env('MDB_LOCAL_MUNICIPALITIES_URL', MdbGeographyDownloader::LOCAL_MUNICIPALITIES_URL),
            ...$csvToArray(env('MDB_LOCAL_MUNICIPALITIES_FALLBACK_URLS')),
            MdbGeographyDownloader::LOCAL_MUNICIPALITIES_FALLBACK_URL,
        ])),
        'wards' => array_values(array_filter([
            env('MDB_WARDS_URL', MdbGeographyDownloader::WARDS_URL),
            ...$csvToArray(env('MDB_WARDS_FALLBACK_URLS')),
            MdbGeographyDownloader::WARDS_FALLBACK_URL,
        ])),
    ],
];
