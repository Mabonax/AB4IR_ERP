<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'system:backup-database {--prune : Prune expired backups after creating a new one}';

    protected $description = 'Create a database backup on the configured filesystem disk.';

    public function handle(): int
    {
        if (! config('backup.enabled', true)) {
            $this->warn('Database backups are disabled.');

            return self::SUCCESS;
        }

        $connection = (string) config('database.default');

        return match ($connection) {
            'sqlite' => $this->backupSqlite(),
            'mysql', 'mariadb' => $this->backupMysqlLike($connection),
            default => $this->unsupportedConnection($connection),
        };
    }

    protected function backupSqlite(): int
    {
        $databasePath = (string) config('database.connections.sqlite.database');

        if ($databasePath === '' || $databasePath === ':memory:') {
            $this->error('SQLite in-memory databases cannot be backed up to disk.');

            return self::FAILURE;
        }

        if (! File::exists($databasePath)) {
            $this->error("SQLite database file not found at [{$databasePath}].");

            return self::FAILURE;
        }

        $disk = Storage::disk(config('backup.disk', 'local'));
        $filename = $this->backupFilename('sqlite');
        $disk->put($filename, gzencode(File::get($databasePath), 9));

        $this->info("SQLite backup created at [{$filename}].");

        if ($this->option('prune')) {
            $this->call('system:prune-backups');
        }

        return self::SUCCESS;
    }

    protected function backupMysqlLike(string $driver): int
    {
        $database = (string) config("database.connections.{$driver}.database");
        $host = (string) config("database.connections.{$driver}.host", '127.0.0.1');
        $port = (string) config("database.connections.{$driver}.port", '3306');
        $username = (string) config("database.connections.{$driver}.username", 'root');
        $password = (string) config("database.connections.{$driver}.password", '');
        $dumpBinary = (string) config('backup.mysql_dump_binary', 'mysqldump');

        $process = new Process([
            $dumpBinary,
            '--host='.$host,
            '--port='.$port,
            '--user='.$username,
            '--single-transaction',
            '--quick',
            '--lock-tables=false',
            $database,
        ]);
        $process->setEnv(['MYSQL_PWD' => $password]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error(trim($process->getErrorOutput()) ?: 'mysqldump failed.');

            return self::FAILURE;
        }

        $disk = Storage::disk(config('backup.disk', 'local'));
        $filename = $this->backupFilename('sql');
        $disk->put($filename, gzencode($process->getOutput(), 9));

        $this->info("Database backup created at [{$filename}].");

        if ($this->option('prune')) {
            $this->call('system:prune-backups');
        }

        return self::SUCCESS;
    }

    protected function unsupportedConnection(string $connection): int
    {
        $this->error("Database backup is not configured for the [{$connection}] connection.");

        return self::FAILURE;
    }

    protected function backupFilename(string $extension): string
    {
        $prefix = trim((string) config('backup.path', 'backups/database'), '/');

        return $prefix.'/'.now()->format('Ymd_His').".{$extension}.gz";
    }
}
