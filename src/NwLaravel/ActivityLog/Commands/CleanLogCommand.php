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
    protected $name = 'activitylog:clean';

    /**
     * @var ActivityManager
     */
    protected $activity;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old records from the activity log.';

    /**
     * Construct
     *
     * @param ActivityManager $activity
     */
    public function __construct(ActivityManager $activity)
    {
        parent::__construct();
        $this->activity = $activity;
    }

    /**
     * Handle Command
     *
     * @return void
     */
    public function fire()
    {
        $this->comment('Cleaning activity log...');

        $amountDeleted = $this->activity->cleanLog();

        $this->info("Deleted {$amountDeleted} record(s) from the activity log.");

        $this->comment('All done!');
    }
}
