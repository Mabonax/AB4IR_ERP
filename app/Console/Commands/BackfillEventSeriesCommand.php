<?php

namespace App\Console\Commands;

use App\Domains\Events\Services\EventSeriesService;
use Illuminate\Console\Command;

class BackfillEventSeriesCommand extends Command
{
    protected $signature = 'events:backfill-series {--dry-run : Report what would be linked without writing changes}';

    protected $description = 'Create Event Series records from legacy annual_series_key values and link matching event iterations.';

    public function handle(EventSeriesService $service): int
    {
        $result = $service->backfillFromLegacyKeys((bool) $this->option('dry-run'));

        if ($result['duplicates'] !== []) {
            $this->error('Duplicate series/year combinations were detected. No records were changed.');
            foreach ($result['duplicates'] as $ids) {
                $this->line(' - Event IDs: '.implode(', ', $ids));
            }

            return self::FAILURE;
        }

        $prefix = $result['dry_run'] ? 'Dry run: ' : '';
        $this->info($prefix.'Event series backfill complete.');
        $this->line('Created series: '.$result['created_series']);
        $this->line('Linked events: '.$result['linked_events']);
        $this->line('Skipped events without year: '.$result['skipped_events']);

        return self::SUCCESS;
    }
}
