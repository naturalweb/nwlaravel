<?php

namespace Tests;

use Tests\TestCase;
use Mockery as m;
use NwLaravel\ActivityLog\ActivityManager;
use NwLaravel\ActivityLog\Commands\CleanLogCommand;

class CleanLogCommandTest extends TestCase
{
    public function testConstruct()
    {
        $activity = m::mock(ActivityManager::class);
        $activity->shouldreceive('cleanLog')->once()->andReturn(56);

        $command = m::mock(CleanLogCommand::class.'[info, comment]', [$activity]);
        $command->shouldreceive('comment')->twice();
        $command->shouldreceive('info')->once()->with("Deleted 56 record(s) from the activity log.");

        $this->assertInstanceOf('Illuminate\Console\Command', $command);

        $command->handle();
    }
}
