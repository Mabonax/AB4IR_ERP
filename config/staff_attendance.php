<?php

return [
    'timezone' => env('STAFF_ATTENDANCE_TIMEZONE', 'Africa/Johannesburg'),
    'clock_in_cutoff' => env('STAFF_ATTENDANCE_CLOCK_IN_CUTOFF', '09:00'),
    'auto_clock_out_time' => env('STAFF_ATTENDANCE_AUTO_CLOCK_OUT_TIME', '16:30'),
    'history_limit' => (int) env('STAFF_ATTENDANCE_HISTORY_LIMIT', 60),
];
