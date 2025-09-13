<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ActivityLogger;

class TestLogging extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test that all logs are written to laravel.log file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing logging system - all logs should go to laravel.log');
        
        // Test different log types
        ActivityLogger::logAuth('Test Auth Log', ['test' => 'data']);
        ActivityLogger::logSchool('Test School Log', ['action' => 'test']);
        ActivityLogger::logUser('Test User Log', ['operation' => 'test']);
        ActivityLogger::logStudent('Test Student Log', ['activity' => 'test']);
        ActivityLogger::logSecurity('Test Security Log', ['alert' => 'test'], 'warning');
        
        $this->info('Test logs generated. Check storage/logs/laravel.log');
        
        // Show log stats
        $stats = ActivityLogger::getLogStats();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Entries', $stats['total_entries']],
                ['Info', $stats['info_count']],
                ['Warning', $stats['warning_count']],
                ['Error', $stats['error_count']],
                ['Debug', $stats['debug_count']],
            ]
        );
        
        return self::SUCCESS;
    }
}
