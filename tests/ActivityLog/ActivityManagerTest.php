<?php

namespace Tests;

use Tests\TestCase;
use Mockery as m;
use NwLaravel\ActivityLog\ActivityManager;

class ActivityManagerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->handler = m::mock('NwLaravel\ActivityLog\Handlers\HandlerInterface');
        $this->auth = m::mock('Illuminate\Auth\AuthManager');
        $this->config = m::mock('Illuminate\Config\Repository');
    }

    public function testConstruct()
    {
        $manager = new ActivityManager($this->handler, $this->auth, $this->config);
        $this->assertAttributeEquals($this->handler, 'handler', $manager);
        $this->assertAttributeEquals($this->auth, 'auth', $manager);
        $this->assertAttributeEquals($this->config, 'config', $manager);
    }

    public function testLogWithAuthGuard()
    {
        $user = m::mock('Illuminate\Contracts\Auth\Authenticatable');
        $guard = m::mock('Auth\Guard');
        $guard->shouldReceive('user')->once()->andReturn($user);
        $model = new \stdClass;
        $model->id = 44;
        $request = m::mock('Illuminate\Http\Request');
        $this->app->instance('request', $request);

        $this->config->shouldReceive('get')->once()->with('nwlaravel.activity.auth_guard')->andReturn('foo-guard');
        $this->auth->shouldReceive('guard')->once()->with('foo-guard')->andReturn($guard);
        $this->handler->shouldReceive('log')->once()->with('created', 'test-foo', $model, $user, $request)->andReturn(true);

        $manager = new ActivityManager($this->handler, $this->auth, $this->config);
        
        $this->assertTrue($manager->log('created', 'test-foo', $model));
    }

    public function testLogWithAuthGuardDefault()
    {
        $user = m::mock('Illuminate\Contracts\Auth\Authenticatable');
        $guard = m::mock('Auth\Guard');
        $guard->shouldReceive('user')->once()->andReturn($user);
        $model = new \stdClass;
        $model->id = 44;
        $request = m::mock('Illuminate\Http\Request');
        $this->app->instance('request', $request);

        $this->config->shouldReceive('get')->once()->with('nwlaravel.activity.auth_guard')->andReturn(null);
        $this->auth->shouldReceive('getDefaultDriver')->once()->andReturn('users-guard');
        $this->auth->shouldReceive('guard')->once()->with('users-guard')->andReturn($guard);
        $this->handler->shouldReceive('log')->once()->with('updated', 'test-bar', $model, $user, $request)->andReturn(true);

        $manager = new ActivityManager($this->handler, $this->auth, $this->config);
        
        $this->assertTrue($manager->log('updated', 'test-bar', $model));
    }

    public function testCleanLog()
    {
        $this->config->shouldReceive('get')->once()->with('nwlaravel.activity.deleteOlderThanMonths')->andReturn(3);
        $this->handler->shouldReceive('cleanLog')->once()->with(3)->andReturn(12);

        $manager = new ActivityManager($this->handler, $this->auth, $this->config);
        
        $this->assertEquals(12, $manager->cleanLog());
    }
}
