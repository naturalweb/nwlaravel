<?php

namespace Tests;

use Tests\TestCase;
use Mockery as m;
use NwLaravel\ActivityLog\ActivityLog;

class ActivityLogTest extends TestCase
{
    public function testImplements()
    {
        $entity = new ActivityLog;
        $this->assertInstanceOf('NwLaravel\Entities\AbstractEntity', $entity);
        $this->assertEquals('activity_log', $entity->getTable());
        $this->assertAttributeEquals([
            'action',
            'user_id',
            'description',
            'details',
            'ip_address',
            'content_type',
            'content_id',
            'created_at',
            'updated_at',
        ], 'columns', $entity);
    }

    /**
     * @dataProvider providerGetIcon
     */
    public function testGetIcon($action, $icon)
    {
        $config = m::mock('Config');
        $nwlaravel = include __DIR__.'/../../src/config/nwlaravel.php';
        $config->shouldReceive('get')->with('nwlaravel.activity.action_icon', null)->andReturn($nwlaravel['activity']['action_icon']);

        $this->app->instance('config', $config);

        $entity = new ActivityLog;
        $entity->action = $action;
        
        $this->assertEquals($icon, $entity->getIcon());
        $this->assertEquals('<span class="fa fa-'.$icon.'"></span>', $entity->getIconMarkup());
    }

    public function providerGetIcon()
    {
        return [
            ['default', 'info-circle'],
            ['foo-bar', 'info-circle'],
            ['created', 'plus-circle'],
            ['updated', 'edit'],
            ['deleted', 'minus-circle'],
            ['view', 'eye'],
            ['login', 'sign-in'],
            ['logout', 'sign-out'],
            ['executed', 'ban'],
            ['send', 'envelope'],
            ['upload', 'cloud-upload'],
            ['download', 'cloud-download'],
            ['info', 'info-circle'],
        ];
    }
}
