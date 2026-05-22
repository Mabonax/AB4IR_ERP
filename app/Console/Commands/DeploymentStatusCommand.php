<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class DeploymentStatusCommand extends Command
{
    protected $signature = 'system:deployment-status {--json : Emit machine-readable JSON output}';

    protected $description = 'Report deployment metadata and core runtime diagnostics.';

    public function handle(): int
    {
        $storageLinkTarget = public_path('storage');
        $schedulerHeartbeatFile = '/tmp/scheduler-heartbeat';
        $schedulerHeartbeatAge = null;

        if (is_file($schedulerHeartbeatFile)) {
            $lastHeartbeat = (int) trim((string) file_get_contents($schedulerHeartbeatFile));
            if ($lastHeartbeat > 0) {
                $schedulerHeartbeatAge = max(0, now()->timestamp - $lastHeartbeat);
            }
        }

        $status = [
            'release' => [
                'sha' => env('RELEASE_SHA', 'unknown'),
                'deployed_at' => env('DEPLOYED_AT', 'unknown'),
                'app_runtime_image' => env('APP_RUNTIME_IMAGE_REF', env('APP_RUNTIME_IMAGE', 'unknown')),
                'web_runtime_image' => env('WEB_RUNTIME_IMAGE_REF', env('WEB_RUNTIME_IMAGE', 'unknown')),
                'app_env' => config('app.env'),
            ],
            'checks' => [
                'storage_link_exists' => is_link($storageLinkTarget),
                'storage_link_target' => is_link($storageLinkTarget) ? readlink($storageLinkTarget) : null,
                'storage_writable' => is_writable(storage_path()),
                'database_connected' => false,
                'redis_required' => in_array((string) env('QUEUE_CONNECTION'), ['redis'], true)
                    || in_array((string) env('CACHE_STORE'), ['redis'], true)
                    || in_array((string) env('SESSION_DRIVER'), ['redis'], true),
                'redis_connected' => false,
                'scheduler_heartbeat_age_seconds' => $schedulerHeartbeatAge,
            ],
        ];

        try {
            DB::connection()->getPdo();
            $status['checks']['database_connected'] = true;
        } catch (\Throwable) {
            $status['checks']['database_connected'] = false;
        }

        if ($status['checks']['redis_required']) {
            try {
                $response = Redis::connection()->ping();
                $status['checks']['redis_connected'] = in_array((string) $response, ['1', 'PONG'], true);
            } catch (\Throwable) {
                $status['checks']['redis_connected'] = false;
            }
        } else {
            $status['checks']['redis_connected'] = true;
        }

        if ($this->option('json')) {
            $this->line(json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($status);
        }

        $this->info('Deployment status');
        $this->line('Release SHA: '.$status['release']['sha']);
        $this->line('Deployed at: '.$status['release']['deployed_at']);
        $this->line('App image: '.$status['release']['app_runtime_image']);
        $this->line('Web image: '.$status['release']['web_runtime_image']);
        $this->line('Environment: '.$status['release']['app_env']);
        $this->line('Storage link exists: '.($status['checks']['storage_link_exists'] ? 'yes' : 'no'));
        $this->line('Storage writable: '.($status['checks']['storage_writable'] ? 'yes' : 'no'));
        $this->line('Database connected: '.($status['checks']['database_connected'] ? 'yes' : 'no'));
        $this->line('Redis required: '.($status['checks']['redis_required'] ? 'yes' : 'no'));
        $this->line('Redis connected: '.($status['checks']['redis_connected'] ? 'yes' : 'no'));
        $this->line('Scheduler heartbeat age: '.($schedulerHeartbeatAge !== null ? $schedulerHeartbeatAge.'s' : 'unavailable'));

        return $this->exitCode($status);
    }

    protected function exitCode(array $status): int
    {
        return $status['checks']['storage_link_exists']
            && $status['checks']['storage_writable']
            && $status['checks']['database_connected']
            && (! $status['checks']['redis_required'] || $status['checks']['redis_connected'])
            ? self::SUCCESS
            : self::FAILURE;
    }
}
