<?php

namespace Tests\Repositories\Criterias\Filters;

use Mockery as m;
use Tests\TestCase;
use NwLaravel\Repositories\Criterias\Filters\FilterClosure;
use NwLaravel\Repositories\Criterias\Filters\FilterInterface;

class FilterClosureTest extends TestCase
{
    public function testFilterValid()
    {
        $filter = new FilterClosure;
        $this->assertInstanceOf(FilterInterface::class, $filter);

        $key = 0;
        $value = function(){};
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->once()->with($value)->andReturn($query);
        $this->assertTrue($filter->filter($query, $key, $value));
    }

    public function testFilterInvalid()
    {
        $filter = new FilterClosure;
        $this->assertInstanceOf(FilterInterface::class, $filter);

        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->never();
        $this->assertFalse($filter->filter($query, 'key', 'foobar'));
    }
}
