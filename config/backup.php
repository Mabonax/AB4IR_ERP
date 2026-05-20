<?php

return [
    'enabled' => env('BACKUP_ENABLED', true),
    'disk' => env('BACKUP_DISK', 'local'),
    'path' => env('BACKUP_PATH', 'backups/database'),
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 14),
    'mysql_dump_binary' => env('DB_DUMP_BINARY', 'mysqldump'),
];
