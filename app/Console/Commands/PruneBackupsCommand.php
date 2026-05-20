<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class PruneBackupsCommand extends Command
{
    protected $signature = 'system:prune-backups';

    protected $description = 'Delete expired database backups from the configured filesystem disk.';

    public function handle(): int
    {
        $disk = Storage::disk(config('backup.disk', 'local'));
        $prefix = trim((string) config('backup.path', 'backups/database'), '/');
        $retentionDays = max(1, (int) config('backup.retention_days', 14));
        $cutoff = now()->subDays($retentionDays);
        $deleted = 0;

        foreach ($disk->files($prefix) as $file) {
            $lastModified = Carbon::createFromTimestamp($disk->lastModified($file));

            if ($lastModified->lessThan($cutoff)) {
                $disk->delete($file);
                $deleted++;
            }
        }

        $this->info("Expired backups deleted: {$deleted}");

        return self::SUCCESS;
    }
}
