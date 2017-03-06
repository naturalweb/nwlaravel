<?php

namespace Tests\Repositories\Eloquent;

use Mockery as m;
use Tests\TestCase;
use NwLaravel\Repositories\Eloquent\CacheableRepository;

class CacheableRepositoryTest extends TestCase
{
    public function testSetCacheRepository()
    {
        $cache = m::mock('Illuminate\Cache\Repository');

        $repo = new StubCacheable;

        $this->assertEquals($repo, $repo->setCacheRepository($cache));

        $this->assertAttributeEquals($cache, 'cacheRepository', $repo);
    }

    public function testSkipCache()
    {
        $cache = m::mock('Illuminate\Cache\Repository');

        $repo = new StubCacheable;

        $this->assertAttributeEquals(false, 'cacheSkip', $repo);

        $this->assertEquals($repo, $repo->skipCache());

        $this->assertAttributeEquals(true, 'cacheSkip', $repo);
    }

    public function testIsSkippedCache()
    {
        $cache = m::mock('Illuminate\Cache\Repository');

        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('has')->once()->with('skipCache')->andReturn(true);
        $request->shouldReceive('get')->once()->with('skipCache')->andReturn(true);
        $this->app->instance('Illuminate\Http\Request', $request);

        $config = m::mock('Illuminate\Config\Repository');
        $config->shouldReceive('get')->with('repository.cache.params.skipCache', 'skipCache')->andReturn('skipCache');
        $this->app->instance('config', $config);

        
        $repo = new StubCacheable;
        $this->assertTrue($repo->isSkippedCache());
    }

    public function testCallCache()
    {
        $config = m::mock('Illuminate\Config\Repository');
        $config->shouldReceive('get')->with('repository.cache.repository', 'cache')->andReturn('cache');
        $config->shouldReceive('get')->with('repository.cache.enabled', true)->andReturn(true);
        $config->shouldReceive('get')->with('repository.cache.params.skipCache', 'skipCache')->andReturn(null);
        $config->shouldReceive('get')->with('repository.cache.allowed.only', null)->andReturn(null);
        $config->shouldReceive('get')->with('repository.cache.allowed.except', null)->andReturn(null);
        $config->shouldReceive('get')->with('repository.cache.minutes', 30)->andReturn(30);
        $this->app->instance('config', $config);

        $return = 'content-cache';

        $cache = m::mock('Illuminate\Cache\Repository');
        $cache->shouldReceive('tags')->andReturn($cache);
        $cache->shouldReceive('remember')->once()->andReturn($return);
        $this->app->instance('cache', $cache);

        $mock = m::mock(StubCacheable::class.'[getCriteria]');

        $criteria = ['where' => '1'];
        $mock->shouldReceive('getCriteria')->once()->andReturn($criteria);

        $this->assertEquals($return, $mock->callCache('foobar', ['bar']));
    }

    public function testAll()
    {
        $mock = m::mock(StubCacheable::class.'[callCache]');

        $return = 'content-cache';
        $columns = ['id'];
        $mock->shouldReceive('callCache')->with('all', [$columns])->once()->andReturn($return);

        $this->assertEquals($return, $mock->all($columns));
    }

    public function testPaginate()
    {
        $mock = m::mock(StubCacheable::class.'[callCache]');

        $return = 'content-cache';
        $limit = 3;
        $columns = ['id'];
        $mock->shouldReceive('callCache')->with('paginate', [$limit, $columns])->once()->andReturn($return);

        $this->assertEquals($return, $mock->paginate($limit, $columns));
    }

    public function testFind()
    {
        $mock = m::mock(StubCacheable::class.'[callCache]');

        $return = 'content-cache';
        $columns = ['id'];
        $mock->shouldReceive('callCache')->with('find', [$columns])->once()->andReturn($return);

        $this->assertEquals($return, $mock->find($columns));
    }

    public function testFindByField()
    {
        $mock = m::mock(StubCacheable::class.'[callCache]');

        $return = 'content-cache';
        $field = 'foobar';
        $value = 'test';
        $columns = ['id'];
        $mock->shouldReceive('callCache')->with('findByField', [$field, $value, $columns])->once()->andReturn($return);

        $this->assertEquals($return, $mock->findByField($field, $value, $columns));
    }

    public function testFindWhere()
    {
        $mock = m::mock(StubCacheable::class.'[callCache]');

        $return = 'content-cache';
        $where = ['name' => 'bar'];
        $columns = ['id'];
        $mock->shouldReceive('callCache')->with('findWhere', [$where, $columns])->once()->andReturn($return);

        $this->assertEquals($return, $mock->findWhere($where, $columns));
    }

    public function testGetByCriteria()
    {
        $mock = m::mock(StubCacheable::class.'[callCache]');

        $return = 'content-cache';
        $criteria = m::mock('Prettus\Repository\Contracts\CriteriaInterface');
        $mock->shouldReceive('callCache')->with('getByCriteria', [$criteria])->once()->andReturn($return);

        $this->assertEquals($return, $mock->getByCriteria($criteria));
    }

    public function testPluck()
    {
        $mock = m::mock(StubCacheable::class.'[callCache]');

        $return = 'content-cache';
        $where = ['name' => 'bar'];
        $mock->shouldReceive('callCache')->with('pluck', [$where])->once()->andReturn($return);

        $this->assertEquals($return, $mock->pluck($where));
    }

    public function testCount()
    {
        $mock = m::mock(StubCacheable::class.'[callCache]');

        $return = 'content-cache';
        $where = ['name' => 'bar'];
        $columns = ['id'];
        $mock->shouldReceive('callCache')->with('count', [$where, $columns])->once()->andReturn($return);

        $this->assertEquals($return, $mock->count($where, $columns));
    }

    public function testMax()
    {
        $mock = m::mock(StubCacheable::class.'[callCache]');

        $return = 'content-cache';
        $where = ['name' => 'bar'];
        $field = 'foobar';
        $mock->shouldReceive('callCache')->with('max', [$field, $where])->once()->andReturn($return);

        $this->assertEquals($return, $mock->max($field, $where));
    }

    public function testMin()
    {
        $mock = m::mock(StubCacheable::class.'[callCache]');

        $return = 'content-cache';
        $where = ['name' => 'bar'];
        $field = 'foobar';
        $mock->shouldReceive('callCache')->with('min', [$field, $where])->once()->andReturn($return);

        $this->assertEquals($return, $mock->min($field, $where));
    }

    public function testSum()
    {
        $mock = m::mock(StubCacheable::class.'[callCache]');

        $return = 'content-cache';
        $where = ['name' => 'bar'];
        $field = 'foobar';
        $mock->shouldReceive('callCache')->with('sum', [$field, $where])->once()->andReturn($return);

        $this->assertEquals($return, $mock->sum($field, $where));
    }

    public function testAvg()
    {
        $mock = m::mock(StubCacheable::class.'[callCache]');

        $return = 'content-cache';
        $where = ['name' => 'bar'];
        $field = 'foobar';
        $mock->shouldReceive('callCache')->with('avg', [$field, $where])->once()->andReturn($return);

        $this->assertEquals($return, $mock->avg($field, $where));
    }
}

class StubCacheable
{
    use CacheableRepository;
}
