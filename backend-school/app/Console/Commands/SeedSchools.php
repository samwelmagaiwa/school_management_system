<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\SchoolSeeder;
use Database\Seeders\SafeSchoolSeeder;

class SeedSchools extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schools:seed {--fresh : Clear existing schools before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with sample schools';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Seeding schools...');
        
        if ($this->option('fresh')) {
            $this->warn('Using fresh mode - will attempt to clear existing schools...');
            $seeder = new SchoolSeeder();
        } else {
            $this->info('Using safe mode - will skip if schools already exist...');
            $seeder = new SafeSchoolSeeder();
        }
        
        $seeder->setCommand($this);
        $seeder->run();
        
        $this->info('Schools seeding completed!');
        
        return Command::SUCCESS;
    }
}