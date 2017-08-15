<?php

namespace Tests\Repositories\Criterias\Filters;

use Mockery as m;
use Tests\TestCase;
use NwLaravel\Repositories\Criterias\Filters\FilterArray;
use NwLaravel\Repositories\Criterias\Filters\FilterInterface;

class FilterArrayTest extends TestCase
{
    public function testFilterImplements()
    {
        $table = 'foobar';
        $columns = ['id', 'name'];
        $dates = ['created_at'];

        $filter = new FilterArray($table, $columns, $dates);
        $this->assertInstanceOf(FilterInterface::class, $filter);
        $this->assertAttributeEquals($table, 'table', $filter);
        $this->assertAttributeEquals($columns, 'columns', $filter);
        $this->assertAttributeEquals($dates, 'dates', $filter);
    }

    public function testFilterValidWithString()
    {
        $table = 'foobar';
        $columns = ['id', 'price'];
        $dates = ['created_at'];

        $filter = new FilterArray($table, $columns, $dates);

        $key = 1;
        $value = 'price,>,1';
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->once()->with('foobar.price', '>', '1')->andReturn($query);
        $this->assertTrue($filter->filter($query, $key, $value));
    }

    public function testFilterValidWithArray()
    {
        $table = 'foobar';
        $columns = ['id', 'name'];
        $dates = ['created_at'];

        $filter = new FilterArray($table, $columns, $dates);

        $key = 1;
        $value = ['name', '!=', 'maria'];
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->once()->with('foobar.name', '!=', 'maria')->andReturn($query);
        $this->assertTrue($filter->filter($query, $key, $value));
    }

    public function testFilterKeyInvalid()
    {
        $table = 'foobar';
        $columns = ['id', 'name'];
        $dates = ['created_at'];

        $filter = new FilterArray($table, $columns, $dates);

        $key = 'a';
        $value = 'name,=,maria';
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->never();
        $this->assertFalse($filter->filter($query, $key, $value));
    }

    public function testFilterStringInvalid()
    {
        $table = 'foobar';
        $columns = ['id', 'name'];
        $dates = ['created_at'];

        $filter = new FilterArray($table, $columns, $dates);

        $key = 1;
        $value = 'name = maria';
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->never();
        $this->assertFalse($filter->filter($query, $key, $value));
    }

    public function testFilterArrayInvalid()
    {
        $table = 'foobar';
        $columns = ['id', 'name'];
        $dates = ['created_at'];

        $filter = new FilterArray($table, $columns, $dates);

        $key = 1;
        $value = [];
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->never();
        $this->assertFalse($filter->filter($query, $key, $value));
    }
}
