<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ValidateDeploymentCommand extends Command
{
    protected $signature = 'system:validate-deployment
        {--services : Validate database and Redis connectivity}
        {--strict : Fail on warnings as well as errors}';

    protected $description = 'Validate production deployment environment integrity and dependent services.';

    public function handle(): int
    {
        $errors = [];
        $warnings = [];

        $requiredVariables = [
            'APP_NAME',
            'APP_ENV',
            'APP_KEY',
            'APP_URL',
            'DB_CONNECTION',
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'DB_USERNAME',
            'QUEUE_CONNECTION',
            'SESSION_DRIVER',
            'CACHE_STORE',
        ];

        foreach ($requiredVariables as $variable) {
            if (blank(env($variable))) {
                $errors[] = "{$variable} must be set.";
            }
        }

        if (env('APP_NAME') === 'Laravel') {
            $warnings[] = 'APP_NAME is still set to the Laravel starter default.';
        }

        if (app()->environment('production') && filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL)) {
            $errors[] = 'APP_DEBUG must be false in production.';
        }

        if (app()->environment('production') && filter_var(env('PUBLIC_REGISTRATION_ENABLED', false), FILTER_VALIDATE_BOOL)) {
            $errors[] = 'PUBLIC_REGISTRATION_ENABLED must remain false in production.';
        }

        foreach ([
            'DB_PASSWORD' => ['secret', 'password', 'change-me-db-password'],
            'DB_ROOT_PASSWORD' => ['root-secret', 'password', 'change-me-root-password'],
            'SUPER_ADMIN_PASSWORD' => ['password', 'change-me-super-admin-password'],
            'STAFF_USER_DEFAULT_PASSWORD' => ['password', 'change-me-staff-password'],
        ] as $variable => $defaultValues) {
            if (in_array((string) env($variable), $defaultValues, true)) {
                $warnings[] = "{$variable} is still using a default or placeholder value.";
            }
        }

        if (env('MAIL_MAILER') === 'log' && app()->environment('production')) {
            $warnings[] = 'MAIL_MAILER is still set to log. Staff notifications will not leave the container.';
        }

        if (! str_starts_with((string) env('APP_URL'), 'https://') && app()->environment('production')) {
            $warnings[] = 'APP_URL is not using HTTPS. Confirm TLS termination and cookie settings.';
        }

        if ($this->option('services')) {
            try {
                DB::connection()->getPdo();
                $this->line('Database connectivity: ok');
            } catch (\Throwable $exception) {
                $errors[] = 'Database connectivity check failed: '.$exception->getMessage();
            }

            if (env('QUEUE_CONNECTION') === 'redis' || env('CACHE_STORE') === 'redis') {
                try {
                    $response = Redis::connection()->ping();

                    if (! in_array((string) $response, ['1', 'PONG'], true)) {
                        $warnings[] = 'Redis responded unexpectedly to ping.';
                    }

                    $this->line('Redis connectivity: ok');
                } catch (\Throwable $exception) {
                    $errors[] = 'Redis connectivity check failed: '.$exception->getMessage();
                }
            }
        }

        foreach ($errors as $error) {
            $this->error($error);
        }

        foreach ($warnings as $warning) {
            $this->warn($warning);
        }

        if ($errors === [] && $warnings === []) {
            $this->info('Deployment validation passed.');

            return self::SUCCESS;
        }

        if ($errors !== []) {
            return self::FAILURE;
        }

        return $this->option('strict') ? self::FAILURE : self::SUCCESS;
    }
}
