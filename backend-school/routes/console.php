<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\CleanOldLogs;
use App\Console\Commands\LogStats;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Register logging commands
Artisan::command('logs:clean {--days=30}', function () {
    $days = (int) $this->option('days');
    $command = new CleanOldLogs();
    return $command->handle();
})->purpose('Clean old log files');

Artisan::command('logs:stats {channel=activity} {--days=7}', function () {
    $channel = $this->argument('channel');
    $days = (int) $this->option('days');
    $command = new LogStats();
    return $command->handle();
})->purpose('Display log statistics');
