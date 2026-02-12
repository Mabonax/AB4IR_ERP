<?php

use Database\Seeders\AccessControlSeeder;
use Database\Seeders\StaffDepartmentsSeeder;
use Database\Seeders\SuperAdminUserSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('access-control:resync', function () {
    $this->info('Re-syncing departments, roles, permissions, and super admin user...');

    $this->call('db:seed', [
        '--class' => StaffDepartmentsSeeder::class,
        '--force' => true,
    ]);

    $this->call('db:seed', [
        '--class' => AccessControlSeeder::class,
        '--force' => true,
    ]);

    $this->call('db:seed', [
        '--class' => SuperAdminUserSeeder::class,
        '--force' => true,
    ]);

    $this->info('Access-control re-sync complete.');
})->purpose('Safely re-sync departments, roles, permissions, and the seeded super admin user.');
