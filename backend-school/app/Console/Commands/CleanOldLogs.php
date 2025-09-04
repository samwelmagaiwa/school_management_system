<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ActivityLogger;
use Carbon\Carbon;

class CleanOldLogs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'logs:clean {--days=30 : Number of days to keep logs}';

    /**
     * The console command description.
     */
    protected $description = 'Clean old log files to free up disk space';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        
        $this->info("Cleaning log files older than {$days} days...");
        
        $deletedCount = ActivityLogger::cleanOldLogs($days);
        
        if ($deletedCount > 0) {
            $this->info("Successfully deleted {$deletedCount} old log files.");
            
            // Log this cleanup activity
            ActivityLogger::log(
                'Log Cleanup Performed',
                'System',
                [
                    'deleted_files_count' => $deletedCount,
                    'retention_days' => $days,
                    'cleanup_date' => Carbon::now()->toISOString()
                ],
                'info',
                'activity'
            );
        } else {
            $this->info('No old log files found to delete.');
        }
        
        return Command::SUCCESS;
    }
}