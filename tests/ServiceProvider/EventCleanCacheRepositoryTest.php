<?php

namespace Tests\ServiceProvider;

use Tests\TestCase;
use Mockery as m;
use NwLaravel\ServiceProvider\EventCleanCacheRepository;

class EventCleanCacheRepositoryTest extends TestCase
{
    public function testBoot()
    {
        $listen = [
            'Prettus\Repository\Events\RepositoryEntityCreated' => [
                'NwLaravel\Repositories\Listeners\CleanCacheRepository'
            ],
            'Prettus\Repository\Events\RepositoryEntityUpdated' => [
                'NwLaravel\Repositories\Listeners\CleanCacheRepository'
            ],
            'Prettus\Repository\Events\RepositoryEntityDeleted' => [
                'NwLaravel\Repositories\Listeners\CleanCacheRepository'
            ]
        ];

        $events = m::mock('Illuminate\Events\Dispatcher');
        $this->app->instance('events', $events);
        foreach ($listen as $key => $values) {
          $events->shouldReceive('listen')->once()->with($key, $values[0]);
        }

        $eventClean = new EventCleanCacheRepository($this->app);
        $eventClean->boot();
        $eventClean->register();
    }
}
