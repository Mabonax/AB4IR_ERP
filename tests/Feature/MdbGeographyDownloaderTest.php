<?php

use App\Domains\Geography\Services\MdbGeographyDownloader;
use Illuminate\Support\Facades\Http;

test('mdb geography downloader merges paginated geojson responses and writes output files', function () {
    resetMdbImportArtifacts();
    config()->set('mdb.sources.local_municipalities', [MdbGeographyDownloader::LOCAL_MUNICIPALITIES_URL]);

    Http::fake([
        MdbGeographyDownloader::LOCAL_MUNICIPALITIES_URL.'*' => Http::sequence()
            ->push([
                'type' => 'FeatureCollection',
                'features' => [
                    [
                        'type' => 'Feature',
                        'properties' => ['code' => 'LIM331', 'name' => 'Municipality One'],
                        'geometry' => ['type' => 'Polygon', 'coordinates' => []],
                    ],
                    [
                        'type' => 'Feature',
                        'properties' => ['code' => 'LIM332', 'name' => 'Municipality Two'],
                        'geometry' => ['type' => 'Polygon', 'coordinates' => []],
                    ],
                ],
                'exceededTransferLimit' => true,
            ], 200)
            ->push([
                'type' => 'FeatureCollection',
                'features' => [
                    [
                        'type' => 'Feature',
                        'properties' => ['code' => 'LIM333', 'name' => 'Municipality Three'],
                        'geometry' => ['type' => 'Polygon', 'coordinates' => []],
                    ],
                ],
                'exceededTransferLimit' => false,
            ], 200),
    ]);

    $service = app(MdbGeographyDownloader::class);
    $features = $service->downloadLocalMunicipalities();

    expect($features)->toHaveCount(3);

    $geoJsonPath = $service->writeGeoJson('mdb_local_municipalities.geojson', $features);
    $csvPath = $service->writeCsv('mdb_local_municipalities_attributes.csv', $features);

    expect(file_exists(storage_path('app/imports/mdb/mdb_local_municipalities.geojson')))->toBeTrue()
        ->and(file_exists(storage_path('app/imports/mdb/mdb_local_municipalities_attributes.csv')))->toBeTrue();

    $geoJson = json_decode(file_get_contents(storage_path('app/imports/mdb/mdb_local_municipalities.geojson')), true);
    $csv = file_get_contents(storage_path('app/imports/mdb/mdb_local_municipalities_attributes.csv'));

    expect($geoJson['features'])->toHaveCount(3)
        ->and($csv)->toContain('code,name')
        ->and($csv)->toContain('LIM333')
        ->and($geoJsonPath)->toBe(storage_path('app/imports/mdb/mdb_local_municipalities.geojson'))
        ->and($csvPath)->toBe(storage_path('app/imports/mdb/mdb_local_municipalities_attributes.csv'));

    Http::assertSentCount(2);
});

test('mdb geography downloader command handles empty responses and still creates all files', function () {
    resetMdbImportArtifacts();
    config()->set('mdb.sources.local_municipalities', [MdbGeographyDownloader::LOCAL_MUNICIPALITIES_URL]);
    config()->set('mdb.sources.wards', [MdbGeographyDownloader::WARDS_URL]);

    Http::fake([
        MdbGeographyDownloader::LOCAL_MUNICIPALITIES_URL.'*' => Http::response([
            'type' => 'FeatureCollection',
            'features' => [],
            'exceededTransferLimit' => false,
        ], 200),
        MdbGeographyDownloader::WARDS_URL.'*' => Http::response([
            'type' => 'FeatureCollection',
            'features' => [],
            'exceededTransferLimit' => false,
        ], 200),
    ]);

    $this->artisan('mdb:download-geography')
        ->expectsOutput('Downloading MDB Local Municipalities...')
        ->expectsOutput('Downloading MDB Wards...')
        ->expectsOutput('MDB geography download completed.')
        ->assertExitCode(0);

    expect(file_exists(storage_path('app/imports/mdb/mdb_local_municipalities.geojson')))->toBeTrue()
        ->and(file_exists(storage_path('app/imports/mdb/mdb_local_municipalities_attributes.csv')))->toBeTrue()
        ->and(file_exists(storage_path('app/imports/mdb/mdb_wards.geojson')))->toBeTrue()
        ->and(file_exists(storage_path('app/imports/mdb/mdb_wards_attributes.csv')))->toBeTrue()
        ->and(json_decode(file_get_contents(storage_path('app/imports/mdb/mdb_local_municipalities.geojson')), true)['features'])->toBe([])
        ->and(json_decode(file_get_contents(storage_path('app/imports/mdb/mdb_wards.geojson')), true)['features'])->toBe([]);
});

test('mdb geography downloader command reports http failures clearly', function () {
    resetMdbImportArtifacts();
    config()->set('mdb.sources.local_municipalities', [MdbGeographyDownloader::LOCAL_MUNICIPALITIES_URL]);
    config()->set('mdb.sources.wards', [MdbGeographyDownloader::WARDS_URL]);

    Http::fake([
        MdbGeographyDownloader::LOCAL_MUNICIPALITIES_URL.'*' => Http::response([], 500),
        MdbGeographyDownloader::WARDS_URL.'*' => Http::response([], 200),
    ]);

    $this->artisan('mdb:download-geography')
        ->expectsOutput('Downloading MDB Local Municipalities...')
        ->expectsOutputToContain('MDB request failed')
        ->assertExitCode(1);
});

test('mdb geography downloader falls back to alternate official sources when the primary source fails', function () {
    resetMdbImportArtifacts();

    config()->set('mdb.sources.local_municipalities', [
        MdbGeographyDownloader::LOCAL_MUNICIPALITIES_URL,
        MdbGeographyDownloader::LOCAL_MUNICIPALITIES_FALLBACK_URL,
    ]);

    Http::fake([
        MdbGeographyDownloader::LOCAL_MUNICIPALITIES_URL.'*' => fn () => throw new RuntimeException('DNS failure'),
        MdbGeographyDownloader::LOCAL_MUNICIPALITIES_FALLBACK_URL.'*' => Http::response([
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'properties' => ['CAT_B' => 'WC032', 'MUNICNAME' => 'Overstrand'],
                    'geometry' => ['type' => 'MultiPolygon', 'coordinates' => []],
                ],
            ],
            'properties' => ['exceededTransferLimit' => false],
        ], 200),
    ]);

    $progressMessages = [];
    $features = app(MdbGeographyDownloader::class)->downloadLocalMunicipalities(
        function (int $pageCount, int $totalCount, ?string $message = null) use (&$progressMessages): void {
            if ($message !== null) {
                $progressMessages[] = $message;
            }
        }
    );

    expect($features)->toHaveCount(1)
        ->and($features[0]['properties']['MUNICNAME'])->toBe('Overstrand')
        ->and($progressMessages)->toHaveCount(1)
        ->and($progressMessages[0])->toContain('Primary source unavailable. Trying fallback source');
});

function resetMdbImportArtifacts(): void
{
    $directory = storage_path('app/imports/mdb');

    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    foreach ([
        'mdb_local_municipalities.geojson',
        'mdb_local_municipalities_attributes.csv',
        'mdb_wards.geojson',
        'mdb_wards_attributes.csv',
    ] as $filename) {
        $path = $directory.DIRECTORY_SEPARATOR.$filename;

        if (is_file($path)) {
            unlink($path);
        }
    }
}
