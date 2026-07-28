<?php

namespace App\Console\Commands;

use App\Domains\Geography\Services\MdbGeographyDownloader;
use Illuminate\Console\Command;
use Throwable;

class DownloadMdbGeographyCommand extends Command
{
    protected $signature = 'mdb:download-geography';

    protected $description = 'Download MDB local municipality and ward geography files for future ERP imports.';

    public function __construct(
        protected MdbGeographyDownloader $downloader
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        try {
            $this->downloader->ensureStorageDirectory();

            $this->downloadDataset(
                heading: 'Downloading MDB Local Municipalities...',
                features: fn (string $geoJsonFilename, string $csvFilename): array => $this->downloader->downloadConfiguredLayerToFiles(
                    'local_municipalities',
                    'Local Municipalities',
                    $geoJsonFilename,
                    $csvFilename,
                    function (int $pageCount, int $totalCount, ?string $message = null): void {
                        if ($message !== null) {
                            $this->warn($message);

                            return;
                        }

                        $this->line("Fetched {$pageCount} records...");
                    }
                ),
                geoJsonFilename: 'mdb_local_municipalities.geojson',
                csvFilename: 'mdb_local_municipalities_attributes.csv',
            );

            $this->newLine();

            $this->downloadDataset(
                heading: 'Downloading MDB Wards...',
                features: fn (string $geoJsonFilename, string $csvFilename): array => $this->downloader->downloadConfiguredLayerToFiles(
                    'wards',
                    'Wards',
                    $geoJsonFilename,
                    $csvFilename,
                    function (int $pageCount, int $totalCount, ?string $message = null): void {
                        if ($message !== null) {
                            $this->warn($message);

                            return;
                        }

                        $this->line("Fetched {$pageCount} records...");
                    }
                ),
                geoJsonFilename: 'mdb_wards.geojson',
                csvFilename: 'mdb_wards_attributes.csv',
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('MDB geography download completed.');

        return self::SUCCESS;
    }

    protected function downloadDataset(string $heading, callable $features, string $geoJsonFilename, string $csvFilename): void
    {
        $this->info($heading);

        $savedFiles = $features($geoJsonFilename, $csvFilename);

        $this->line("Saved {$savedFiles['geojson_path']}");
        $this->line("Saved {$savedFiles['csv_path']}");
    }
}
