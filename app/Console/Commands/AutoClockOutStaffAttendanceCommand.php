<?php

namespace App\Console\Commands;

use App\Domains\StaffAttendance\Services\StaffAttendanceService;
use Illuminate\Console\Command;

class AutoClockOutStaffAttendanceCommand extends Command
{
    protected $signature = 'staff-attendance:auto-clock-out';

    protected $description = 'Automatically clock out any open staff attendance records at the daily cut-off time.';

    public function handle(StaffAttendanceService $service): int
    {
        $count = $service->autoClockOutOpenRecords();

        $this->info("Auto clock-out complete. Records updated: {$count}");

        return self::SUCCESS;
    }
}
