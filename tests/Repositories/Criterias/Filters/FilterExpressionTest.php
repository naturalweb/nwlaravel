<?php

namespace Tests\Repositories\Criterias\Filters;

use Mockery as m;
use Tests\TestCase;
use NwLaravel\Repositories\Criterias\Filters\FilterExpression;
use NwLaravel\Repositories\Criterias\Filters\FilterInterface;
use Illuminate\Database\Query\Expression;

class FilterExpressionTest extends TestCase
{
    public function testFilterValid()
    {
        $filter = new FilterExpression;
        $this->assertInstanceOf(FilterInterface::class, $filter);

        $key = 0;
        $value = new Expression('field=other');
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('whereRaw')->once()->with($value)->andReturn($query);
        $this->assertTrue($filter->filter($query, $key, $value));
    }

    public function testFilterInvalid()
    {
        $filter = new FilterExpression;
        $this->assertInstanceOf(FilterInterface::class, $filter);

        $key = 0;
        $value = '';
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('whereRaw')->never();
        $this->assertFalse($filter->filter($query, $key, $value));
    }
}
