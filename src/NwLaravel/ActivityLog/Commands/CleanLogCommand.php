<?php
namespace NwLaravel\ActivityLog\Commands;

use Illuminate\Console\Command;
use NwLaravel\ActivityLog\ActivityManager;

class CleanLogCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'activitylog:clean';

    /**
     * @var ActivityManager
     */
    protected $activity;

    /**
     * Construct
     *
     * @param ActivityManager $activity
     */
    public function __construct(ActivityManager $activity)
    {
        $this->activity = $activity;
    }

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old records from the activity log.';

    /**
     * Handle Command
     * 
     * @return void
     */
    public function handle()
    {
        $this->comment('Cleaning activity log...');

        $amountDeleted = $this->activity->cleanLog();

        $this->info("Deleted {$amountDeleted} record(s) from the activity log.");

        $this->comment('All done!');
    }
}