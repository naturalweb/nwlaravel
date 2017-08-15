<?php

namespace Tests\Repositories\Criterias\Filters;

use Mockery as m;
use Tests\TestCase;
use NwLaravel\Repositories\Criterias\Filters\FilterScope;
use NwLaravel\Repositories\Criterias\Filters\FilterInterface;
use Tests\Repositories\Criterias\StubModel;

class FilterScopeTest extends TestCase
{
    public function testFilterValid()
    {
        $model = new StubModel;

        $filter = new FilterScope($model);
        $this->assertInstanceOf(FilterInterface::class, $filter);
        $this->assertAttributeEquals($model, 'model', $filter);

        $key = 'example';
        $value = 'test_scope';
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('example')->once()->with($value)->andReturn($query);
        $this->assertTrue($filter->filter($query, $key, $value));
    }

    public function testFilterInvalid()
    {
        $model = new StubModel;

        $filter = new FilterScope($model);
        $this->assertInstanceOf(FilterInterface::class, $filter);
        $this->assertAttributeEquals($model, 'model', $filter);

        $key = 'other';
        $value = 'test_scope';
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('other')->never();
        $this->assertFalse($filter->filter($query, $key, $value));
    }
}
