<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('operations status reports queue, scheduler, worker, and backup diagnostics', function () {
    config()->set('queue.default', 'database');
    config()->set('backup.disk', 'local');
    config()->set('backup.path', 'backups/database');

    Storage::fake('local');
    Storage::disk('local')->put('backups/database/20260522_100000.sql.gz', 'backup');

    $healthDirectory = storage_path('framework/health');
    if (! is_dir($healthDirectory)) {
        mkdir($healthDirectory, 0777, true);
    }

    file_put_contents($healthDirectory.'/worker-started-at', (string) now()->subMinute()->timestamp);
    file_put_contents($healthDirectory.'/worker-heartbeat', (string) now()->subSeconds(15)->timestamp);
    file_put_contents($healthDirectory.'/scheduler-heartbeat', (string) now()->subSeconds(20)->timestamp);

    DB::table('jobs')->insert([
        [
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'ExampleJob']),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->subMinutes(5)->timestamp,
        ],
        [
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'ExampleJobTwo']),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->subMinutes(2)->timestamp,
        ],
    ]);

    DB::table('failed_jobs')->insert([
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'FailedExampleJob']),
        'exception' => 'RuntimeException: expected test failure',
        'failed_at' => now()->subMinute(),
    ]);

    $exitCode = Artisan::call('system:operations-status', ['--json' => true]);
    $payload = json_decode(trim(Artisan::output()), true);

    expect($exitCode)->toBe(0)
        ->and($payload)->toBeArray()
        ->and($payload['queue']['pending']['driver'])->toBe('database')
        ->and($payload['queue']['pending']['total_depth'])->toBe(2)
        ->and($payload['queue']['failed_jobs_count'])->toBe(1)
        ->and($payload['worker'])->toHaveKeys(['started_at', 'heartbeat_age_seconds', 'queues'])
        ->and($payload['scheduler'])->toHaveKey('heartbeat_age_seconds')
        ->and($payload['backups'])->toHaveKeys(['latest_file', 'latest_created_at'])
        ->and($payload['backups']['latest_file'])->toBe('backups/database/20260522_100000.sql.gz');
});
