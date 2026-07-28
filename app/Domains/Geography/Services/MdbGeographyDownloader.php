<?php

namespace App\Domains\Geography\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use RuntimeException;

class MdbGeographyDownloader
{
    public const LOCAL_MUNICIPALITIES_URL = 'https://csggis.drdlr.gov.za/server/rest/services/Hosted/MDB/FeatureServer/4/query';

    public const WARDS_URL = 'https://csggis.drdlr.gov.za/server/rest/services/Hosted/MDB/FeatureServer/20/query';

    public const LOCAL_MUNICIPALITIES_FALLBACK_URL = 'https://services7.arcgis.com/oeoyTUJC8HEeYsRB/arcgis/rest/services/LocalMunicipalities2018_Final/FeatureServer/0/query';

    public const WARDS_FALLBACK_URL = 'https://services7.arcgis.com/oeoyTUJC8HEeYsRB/arcgis/rest/services/MDB_Wards_2020/FeatureServer/0/query';

    public const STORAGE_DIRECTORY = 'imports/mdb';

    public function __construct(
        protected HttpFactory $http
    ) {}

    public function downloadLocalMunicipalities(?callable $progress = null): array
    {
        return $this->downloadConfiguredLayer('local_municipalities', 'Local Municipalities', $progress);
    }

    public function downloadWards(?callable $progress = null): array
    {
        return $this->downloadConfiguredLayer('wards', 'Wards', $progress);
    }

    public function downloadLayer(string $url, string $name, ?callable $progress = null): array
    {
        $features = [];
        $offset = 0;
        $pageSize = $this->pageSize();

        do {
            $response = $this->requestLayerPage($url, $offset);
            $payload = $response->json();

            if (! is_array($payload)) {
                throw new RuntimeException("MDB {$name} returned an invalid response payload at offset {$offset}.");
            }

            $page = $payload['features'] ?? null;

            if (! is_array($page)) {
                throw new RuntimeException("MDB {$name} response did not contain a valid features array at offset {$offset}.");
            }

            if ($page === []) {
                break;
            }

            $features = [...$features, ...$page];

            if ($progress !== null) {
                $progress(count($page), count($features));
            }

            $hasMore = (bool) ($payload['exceededTransferLimit'] ?? $payload['properties']['exceededTransferLimit'] ?? false)
                || count($page) === $pageSize;
            $offset += count($page);
        } while ($hasMore);

        return $features;
    }

    public function writeGeoJson(string $filename, array $features): string
    {
        $this->ensureStorageDirectory();

        $path = $this->absoluteStoragePath($filename);

        file_put_contents($path, json_encode([
            'type' => 'FeatureCollection',
            'features' => $features,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $path;
    }

    public function writeCsv(string $filename, array $features): string
    {
        $this->ensureStorageDirectory();

        $rows = array_map(
            fn (array $feature): array => is_array($feature['properties'] ?? null) ? $feature['properties'] : [],
            $features
        );

        $headers = collect($rows)
            ->flatMap(fn (array $row): array => array_keys($row))
            ->unique()
            ->values()
            ->all();

        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new RuntimeException("Unable to open an in-memory stream for {$filename}.");
        }

        if ($headers !== []) {
            fputcsv($stream, $headers);

            foreach ($rows as $row) {
                fputcsv(
                    $stream,
                    array_map(static fn (string $header): mixed => $row[$header] ?? '', $headers)
                );
            }
        }

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        file_put_contents($this->absoluteStoragePath($filename), $contents === false ? '' : $contents);

        return $this->absoluteStoragePath($filename);
    }

    public function downloadConfiguredLayerToFiles(
        string $configKey,
        string $name,
        string $geoJsonFilename,
        string $csvFilename,
        ?callable $progress = null
    ): array {
        $urls = array_values(array_filter((array) config("mdb.sources.{$configKey}", [])));

        if ($urls === []) {
            throw new RuntimeException("No MDB sources are configured for {$name}.");
        }

        $failures = [];

        foreach ($urls as $index => $url) {
            try {
                if ($index > 0 && $progress !== null) {
                    $progress(0, 0, "Primary source unavailable. Trying fallback source: {$url}");
                }

                return $this->streamLayerToFiles($url, $name, $geoJsonFilename, $csvFilename, $progress);
            } catch (RuntimeException $exception) {
                $failures[] = $exception->getMessage();
            }
        }

        throw new RuntimeException(
            "All MDB sources failed for {$name}. ".implode(' | ', $failures)
        );
    }

    protected function requestLayerPage(string $url, int $offset): Response
    {
        $pageSize = $this->pageSize();

        try {
            $response = $this->http
                ->acceptJson()
                ->timeout((int) config('mdb.http.timeout_seconds', 300))
                ->retry(3, 500, throw: false)
                ->get($url, [
                    'where' => '1=1',
                    'outFields' => '*',
                    'f' => 'geojson',
                    'returnGeometry' => 'true',
                    'resultOffset' => $offset,
                    'resultRecordCount' => $pageSize,
                ]);
        } catch (\Throwable $exception) {
            throw new RuntimeException(sprintf(
                'MDB request failed for [%s] at offset %d: %s',
                $url,
                $offset,
                $exception->getMessage()
            ), previous: $exception);
        }

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'MDB request failed for [%s] at offset %d with HTTP %d.',
                $url,
                $offset,
                $response->status()
            ));
        }

        return $response;
    }

    public function ensureStorageDirectory(): void
    {
        $directory = storage_path('app/'.self::STORAGE_DIRECTORY);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    protected function storagePath(string $filename): string
    {
        return self::STORAGE_DIRECTORY.'/'.$filename;
    }

    protected function downloadConfiguredLayer(string $configKey, string $name, ?callable $progress = null): array
    {
        $urls = array_values(array_filter((array) config("mdb.sources.{$configKey}", [])));

        if ($urls === []) {
            throw new RuntimeException("No MDB sources are configured for {$name}.");
        }

        $failures = [];

        foreach ($urls as $index => $url) {
            try {
                if ($index > 0 && $progress !== null) {
                    $progress(0, 0, "Primary source unavailable. Trying fallback source: {$url}");
                }

                return $this->downloadLayer($url, $name, $progress);
            } catch (RuntimeException $exception) {
                $failures[] = $exception->getMessage();
            }
        }

        throw new RuntimeException(
            "All MDB sources failed for {$name}. ".implode(' | ', $failures)
        );
    }

    protected function streamLayerToFiles(
        string $url,
        string $name,
        string $geoJsonFilename,
        string $csvFilename,
        ?callable $progress = null
    ): array {
        $this->ensureStorageDirectory();

        $geoJsonPath = $this->absoluteStoragePath($geoJsonFilename);
        $csvPath = $this->absoluteStoragePath($csvFilename);
        $geoJsonTempPath = $geoJsonPath.'.'.Str::uuid().'.part';
        $csvTempPath = $csvPath.'.'.Str::uuid().'.part';

        $geoJsonHandle = fopen($geoJsonTempPath, 'wb');
        $csvHandle = fopen($csvTempPath, 'wb');

        if ($geoJsonHandle === false || $csvHandle === false) {
            if (is_resource($geoJsonHandle)) {
                fclose($geoJsonHandle);
            }

            if (is_resource($csvHandle)) {
                fclose($csvHandle);
            }

            throw new RuntimeException("Unable to open output files for {$name}.");
        }

        $headers = null;
        $offset = 0;
        $pageSize = $this->pageSize();
        $isFirstFeature = true;
        $totalCount = 0;

        fwrite($geoJsonHandle, '{"type":"FeatureCollection","features":[');

        try {
            do {
                $response = $this->requestLayerPage($url, $offset);
                $payload = $response->json();

                if (! is_array($payload)) {
                    throw new RuntimeException("MDB {$name} returned an invalid response payload at offset {$offset}.");
                }

                $page = $payload['features'] ?? null;

                if (! is_array($page)) {
                    throw new RuntimeException("MDB {$name} response did not contain a valid features array at offset {$offset}.");
                }

                if ($page === []) {
                    break;
                }

                foreach ($page as $feature) {
                    if ($headers === null) {
                        $headers = array_keys(is_array($feature['properties'] ?? null) ? $feature['properties'] : []);

                        if ($headers !== []) {
                            fputcsv($csvHandle, $headers);
                        }
                    }

                    if ($headers !== []) {
                        $properties = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];

                        fputcsv(
                            $csvHandle,
                            array_map(static fn (string $header): mixed => $properties[$header] ?? '', $headers)
                        );
                    }

                    if (! $isFirstFeature) {
                        fwrite($geoJsonHandle, ',');
                    }

                    fwrite(
                        $geoJsonHandle,
                        json_encode($feature, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    );

                    $isFirstFeature = false;
                    $totalCount++;
                }

                if ($progress !== null) {
                    $progress(count($page), $totalCount);
                }

                $hasMore = (bool) ($payload['exceededTransferLimit'] ?? $payload['properties']['exceededTransferLimit'] ?? false)
                    || count($page) === $pageSize;
                $offset += count($page);
            } while ($hasMore);

            fwrite($geoJsonHandle, ']}');
        } catch (\Throwable $exception) {
            fclose($geoJsonHandle);
            fclose($csvHandle);
            @unlink($geoJsonTempPath);
            @unlink($csvTempPath);

            throw $exception instanceof RuntimeException
                ? $exception
                : new RuntimeException($exception->getMessage(), previous: $exception);
        }

        fclose($geoJsonHandle);
        fclose($csvHandle);

        if ($headers === null) {
            file_put_contents($csvTempPath, '');
        }

        @unlink($geoJsonPath);
        @unlink($csvPath);
        rename($geoJsonTempPath, $geoJsonPath);
        rename($csvTempPath, $csvPath);

        return [
            'geojson_path' => $geoJsonPath,
            'csv_path' => $csvPath,
            'count' => $totalCount,
        ];
    }

    protected function pageSize(): int
    {
        return max(1, (int) config('mdb.http.page_size', 100));
    }

    protected function absoluteStoragePath(string $filename): string
    {
        return storage_path('app/'.$this->storagePath($filename));
    }
}
