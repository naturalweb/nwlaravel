<?php

namespace Tests;

use Tests\TestCase;
use Mockery as m;
use Carbon\Carbon;
use NwLaravel\ActivityLog\Handlers\DefaultHandler;
use NwLaravel\ActivityLog\Handlers\HandlerInterface;

class DefaultHandlerTest extends TestCase
{
    public function testCleanLog()
    {
        $maxAgeInMonths = 4;
        $date = Carbon::now()->subMonths($maxAgeInMonths);

        $model = m::mock('NwLaravel\ActivityLog\ActivityLog');
        $model->shouldReceive('where')->once()->with('created_at', '<=', $date->format('Y-m-d'))->andReturn($model);
        $model->shouldReceive('delete')->once()->andReturn(67);

        $handler = new DefaultHandler($model);
        $this->assertEquals(67, $handler->cleanLog($maxAgeInMonths));
    }

    public function testLog()
    {
        $data = [
            'action' => 'created',
            'user_id' => 2,
            'user_name' => 'caused name',
            'description' => 'foo-bar',
            'ip_address' => '192.168.25.25',
            'content_type' => 'stdClass',
            'content_id' => 4,
        ];
        $model = m::mock('NwLaravel\ActivityLog\ActivityLog');
        $model->shouldReceive('create')->once()->with($data)->andReturn(true);

        $config = m::mock('Illuminate\Config\Repository');
        $config->shouldReceive('get')
            ->once()
            ->with('nwlaravel.activity.field_username', null)
            ->andReturn('username');
        $this->app->instance('config', $config);

        $content = new \stdClass;
        $content->id = 4;

        $user = m::mock('Illuminate\Contracts\Auth\Authenticatable');
        $user->shouldReceive('getAuthIdentifier')->once()->andReturn(2);
        $user->username = 'caused name';

        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('ip')->once()->andReturn('192.168.25.25');

        $handler = new DefaultHandler($model);
        $this->assertTrue($handler->log('created', 'foo-bar', $content, $user, $request));
    }
}
