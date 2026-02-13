<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
var_export(app('router')->getRoutes()->hasNamedRoute('attendance-registers.export.pdf'));
