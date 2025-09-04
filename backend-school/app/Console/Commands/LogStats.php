<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ActivityLogger;

class LogStats extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'logs:stats {channel=activity : Log channel to analyze} {--days=7 : Number of days to analyze}';

    /**
     * The console command description.
     */
    protected $description = 'Display statistics for log files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $channel = $this->argument('channel');
        $days = (int) $this->option('days');
        
        $this->info("Analyzing {$channel} logs for the last {$days} days...");
        
        $stats = ActivityLogger::getLogStats($channel, $days);
        
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Entries', $stats['total_entries']],
                ['Info Messages', $stats['info_count']],
                ['Warning Messages', $stats['warning_count']],
                ['Error Messages', $stats['error_count']],
            ]
        );
        
        // Show available log channels
        $this->newLine();
        $this->info('Available log channels:');
        $channels = ['activity', 'auth', 'schools', 'users', 'students', 'security'];
        $this->line('  ' . implode(', ', $channels));
        
        return Command::SUCCESS;
    }
}