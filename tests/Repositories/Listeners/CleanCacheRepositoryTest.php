<?php

namespace Tests\Repositories\Listeners;

use Mockery as m;
use Tests\TestCase;
use Prettus\Repository\Events\RepositoryEventBase;
use NwLaravel\Repositories\Listeners\CleanCacheRepository;

class CleanCacheRepositoryTest extends TestCase
{
    public function testHandle()
    {
        $config = m::mock('Illuminate\Config\Repository');
        $config->shouldReceive('get')->once()->with('repository.cache.repository', 'cache')->andReturn('cache');
        $config->shouldReceive('get')->once()->with('repository.cache.clean.enabled', true)->andReturn(true);
        $config->shouldReceive('get')->once()->with('repository.cache.clean.on.create', true)->andReturn(true);
        $this->app->instance('config', $config);

        $repository = m::mock('NwLaravel\Repositories\Eloquent\AbstractRepository');
        $model = m::mock('Illuminate\Database\Eloquent\Model');

        $tagged = m::mock('Illuminate\Cache\TaggedCache');
        $tagged->shouldReceive('flush')->once()->andReturn(true);

        $cache = m::mock('Illuminate\Cache\Repository');
        $cache->shouldReceive('tags')->once()->with(get_class($repository))->andReturn($tagged);
        $this->app->instance('cache', $cache);

        $eventBase = m::mock(RepositoryEventBase::class);
        $eventBase->shouldReceive('getRepository')->once()->andReturn($repository);
        $eventBase->shouldReceive('getModel')->once()->andReturn($model);
        $eventBase->shouldReceive('getAction')->once()->andReturn('create');

        $cleanCacheRepository = new CleanCacheRepository;
        $cleanCacheRepository->handle($eventBase);
    }
}
