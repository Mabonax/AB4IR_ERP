<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OperationsStatusCommand extends Command
{
    protected $signature = 'system:operations-status {--json : Emit machine-readable JSON output}';

    protected $description = 'Report queue, scheduler, worker, and backup operational diagnostics.';

    public function handle(): int
    {
        $queueConnection = (string) config('queue.default');
        $queueNames = collect(explode(',', (string) env('QUEUE_WORKER_QUEUES', 'default')))
            ->map(fn (string $queue) => trim($queue))
            ->filter()
            ->values();

        if ($queueNames->isEmpty()) {
            $queueNames = collect(['default']);
        }

        $workerHeartbeatAge = $this->heartbeatAge(storage_path('framework/health/worker-heartbeat'));
        $workerStartedAt = $this->heartbeatTimestamp(storage_path('framework/health/worker-started-at'));
        $schedulerHeartbeatAge = $this->heartbeatAge(storage_path('framework/health/scheduler-heartbeat'));

        $failedJobsCount = null;
        $latestFailedJobAt = null;

        if ($this->tableExists('failed_jobs')) {
            $failedJobsCount = DB::table('failed_jobs')->count();
            $latestFailedAt = DB::table('failed_jobs')->max('failed_at');
            $latestFailedJobAt = $latestFailedAt ? Carbon::parse($latestFailedAt)->toIso8601String() : null;
        }

        $pendingJobs = [
            'driver' => $queueConnection,
            'configured_queues' => $queueNames->all(),
            'total_depth' => 0,
            'oldest_pending_age_seconds' => null,
            'by_queue' => [],
        ];

        if ($queueConnection === 'database' && $this->tableExists(config('queue.connections.database.table', 'jobs'))) {
            $table = config('queue.connections.database.table', 'jobs');
            $grouped = DB::table($table)
                ->selectRaw('queue, COUNT(*) as aggregate, MIN(created_at) as oldest_created_at')
                ->groupBy('queue')
                ->get();

            $pendingJobs['by_queue'] = $grouped->map(function ($row) {
                $oldestAge = $row->oldest_created_at
                    ? max(0, now()->timestamp - (int) $row->oldest_created_at)
                    : null;

                return [
                    'queue' => $row->queue,
                    'depth' => (int) $row->aggregate,
                    'oldest_pending_age_seconds' => $oldestAge,
                ];
            })->values()->all();

            $pendingJobs['total_depth'] = array_sum(array_column($pendingJobs['by_queue'], 'depth'));
            $ages = array_filter(array_column($pendingJobs['by_queue'], 'oldest_pending_age_seconds'), fn ($age) => $age !== null);
            $pendingJobs['oldest_pending_age_seconds'] = $ages === [] ? null : max($ages);
        } elseif ($queueConnection === 'redis') {
            $redisConnectionName = (string) config('queue.connections.redis.connection', 'default');
            $queuePrefix = trim((string) config('database.redis.options.prefix', ''), ':');
            $prefix = $queuePrefix === '' ? '' : $queuePrefix.':';

            $pendingJobs['by_queue'] = $queueNames->map(function (string $queueName) use ($redisConnectionName, $prefix) {
                $baseKey = $prefix.'queues:'.$queueName;

                try {
                    $pendingDepth = (int) Redis::connection($redisConnectionName)->llen($baseKey);
                    $reservedDepth = (int) Redis::connection($redisConnectionName)->zcard($baseKey.':reserved');
                    $delayedDepth = (int) Redis::connection($redisConnectionName)->zcard($baseKey.':delayed');
                } catch (\Throwable) {
                    $pendingDepth = 0;
                    $reservedDepth = 0;
                    $delayedDepth = 0;
                }

                return [
                    'queue' => $queueName,
                    'depth' => $pendingDepth,
                    'reserved_depth' => $reservedDepth,
                    'delayed_depth' => $delayedDepth,
                    'oldest_pending_age_seconds' => null,
                ];
            })->values()->all();

            $pendingJobs['total_depth'] = array_sum(array_column($pendingJobs['by_queue'], 'depth'));
        }

        $latestBackup = $this->latestBackup();

        $status = [
            'release' => [
                'sha' => env('RELEASE_SHA', 'unknown'),
                'deployed_at' => env('DEPLOYED_AT', 'unknown'),
            ],
            'worker' => [
                'started_at' => $workerStartedAt,
                'heartbeat_age_seconds' => $workerHeartbeatAge,
                'queues' => $queueNames->all(),
            ],
            'scheduler' => [
                'heartbeat_age_seconds' => $schedulerHeartbeatAge,
                'interval_seconds' => (int) env('SCHEDULER_INTERVAL', 60),
            ],
            'queue' => [
                'pending' => $pendingJobs,
                'failed_jobs_count' => $failedJobsCount,
                'latest_failed_job_at' => $latestFailedJobAt,
            ],
            'backups' => $latestBackup,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Operations status');
        $this->line('Release SHA: '.$status['release']['sha']);
        $this->line('Deployed at: '.$status['release']['deployed_at']);
        $this->line('Worker started at: '.($status['worker']['started_at'] ?? 'unavailable'));
        $this->line('Worker heartbeat age: '.($status['worker']['heartbeat_age_seconds'] !== null ? $status['worker']['heartbeat_age_seconds'].'s' : 'unavailable'));
        $this->line('Scheduler heartbeat age: '.($status['scheduler']['heartbeat_age_seconds'] !== null ? $status['scheduler']['heartbeat_age_seconds'].'s' : 'unavailable'));
        $this->line('Queue driver: '.$status['queue']['pending']['driver']);
        $this->line('Queue total depth: '.(string) $status['queue']['pending']['total_depth']);
        $this->line('Queue oldest pending age: '.($status['queue']['pending']['oldest_pending_age_seconds'] !== null ? $status['queue']['pending']['oldest_pending_age_seconds'].'s' : 'unavailable'));
        $this->line('Failed jobs: '.($status['queue']['failed_jobs_count'] !== null ? (string) $status['queue']['failed_jobs_count'] : 'unavailable'));
        $this->line('Latest failed job: '.($status['queue']['latest_failed_job_at'] ?? 'unavailable'));
        $this->line('Latest backup file: '.($status['backups']['latest_file'] ?? 'unavailable'));
        $this->line('Latest backup created at: '.($status['backups']['latest_created_at'] ?? 'unavailable'));

        if ($status['queue']['pending']['by_queue'] !== []) {
            $this->newLine();
            $this->table(
                ['Queue', 'Depth', 'Reserved', 'Delayed', 'Oldest Pending Age'],
                collect($status['queue']['pending']['by_queue'])->map(fn (array $queue) => [
                    $queue['queue'],
                    $queue['depth'],
                    $queue['reserved_depth'] ?? '-',
                    $queue['delayed_depth'] ?? '-',
                    $queue['oldest_pending_age_seconds'] !== null ? $queue['oldest_pending_age_seconds'].'s' : 'unavailable',
                ])->all()
            );
        }

        return self::SUCCESS;
    }

    protected function heartbeatAge(string $path): ?int
    {
        if (! is_file($path)) {
            return null;
        }

        $timestamp = (int) trim((string) file_get_contents($path));

        return $timestamp > 0 ? max(0, now()->timestamp - $timestamp) : null;
    }

    protected function heartbeatTimestamp(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $timestamp = (int) trim((string) file_get_contents($path));

        return $timestamp > 0 ? now()->createFromTimestamp($timestamp)->toIso8601String() : null;
    }

    protected function latestBackup(): array
    {
        $disk = Storage::disk(config('backup.disk', 'local'));
        $prefix = trim((string) config('backup.path', 'backups/database'), '/');
        $files = collect($disk->files($prefix))->sort()->values();
        $latest = $files->last();

        if (! $latest) {
            return [
                'disk' => config('backup.disk', 'local'),
                'path' => $prefix,
                'latest_file' => null,
                'latest_created_at' => null,
            ];
        }

        return [
            'disk' => config('backup.disk', 'local'),
            'path' => $prefix,
            'latest_file' => $latest,
            'latest_created_at' => $this->timestampFromBackupName($latest),
        ];
    }

    protected function timestampFromBackupName(string $path): ?string
    {
        if (! preg_match('/(\d{8}_\d{6})/', $path, $matches)) {
            return null;
        }

        return Carbon::createFromFormat('Ymd_His', $matches[1], config('app.timezone'))->toIso8601String();
    }

    protected function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
