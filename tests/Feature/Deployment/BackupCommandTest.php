<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('sqlite backups are written to the configured backup disk', function () {
    Storage::fake('local');

    $databasePath = database_path('backup-test.sqlite');
    file_put_contents($databasePath, 'sqlite-backup-payload');

    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.database', $databasePath);
    config()->set('backup.disk', 'local');
    config()->set('backup.path', 'backups/database');

    $this->artisan('system:backup-database')
        ->expectsOutputToContain('SQLite backup created at')
        ->assertSuccessful();

    expect(Storage::disk('local')->files('backups/database'))->toHaveCount(1);

    @unlink($databasePath);
});
