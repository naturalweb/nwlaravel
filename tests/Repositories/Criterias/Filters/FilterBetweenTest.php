<?php

namespace Tests\Repositories\Criterias\Filters;

use Mockery as m;
use Tests\TestCase;
use NwLaravel\Repositories\Criterias\Filters\FilterBetween;
use NwLaravel\Repositories\Criterias\Filters\FilterInterface;

class FilterBetweenTest extends TestCase
{
    public function testFilterImplements()
    {
        $table = 'foobar';
        $columns = ['id', 'name'];
        $dates = ['created_at'];

        $filter = new FilterBetween($table, $columns, $dates);
        $this->assertInstanceOf(FilterInterface::class, $filter);
        $this->assertAttributeEquals($table, 'table', $filter);
        $this->assertAttributeEquals($columns, 'columns', $filter);
        $this->assertAttributeEquals($dates, 'dates', $filter);
    }

    public function testFilterValidColumnValueIniWithoutTable()
    {
        $table = 'foobar';
        $columns = ['id', 'price'];
        $dates = [];

        $filter = new FilterBetween($table, $columns, $dates);

        $key = 'price_ini';
        $value = '10.9';
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->once()->with('foobar.price', '>=', '10.9')->andReturn($query);
        $this->assertTrue($filter->filter($query, $key, $value));
    }

    public function testFilterValidKeyWithTable()
    {
        $table = 'foobar';
        $columns = ['id', 'price'];
        $dates = [];

        $filter = new FilterBetween($table, $columns, $dates);

        $key = 'other.price_fim';
        $value = '20.00';
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->once()->with('other.price', '<=', '20.00')->andReturn($query);
        $this->assertTrue($filter->filter($query, $key, $value));
    }

    public function testFilterColumnDateWithTable()
    {
        $table = 'foobar';
        $columns = ['id', 'created_at'];
        $dates = ['created_at'];

        $filter = new FilterBetween($table, $columns, $dates);

        $key = 'other.created_at_fim';
        $value = '2015-10-31';

        $grammar = m::mock('Illuminate\Database\Query\Grammars\MySqlGrammar[]');
        $queryBuilder = m::mock('Illuminate\Database\Query\Builder');
        $queryBuilder->shouldReceive('getGrammar')->andReturn($grammar);
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('getQuery')->andReturn($queryBuilder);
        $query->shouldReceive('whereRaw')->once()->with("DATE(`other`.`created_at`) <= ?", ['2015-10-31'])->andReturn($query);
        $this->assertTrue($filter->filter($query, $key, $value));
    }

    public function testFilterColumnInvalid()
    {
        $table = 'foobar';
        $columns = ['id', 'price'];
        $dates = [];

        $filter = new FilterBetween($table, $columns, $dates);

        $key = 'price';
        $value = '1';
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->never();
        $this->assertFalse($filter->filter($query, $key, $value));
    }

    public function testFilterColumnInvalidWithTable()
    {
        $table = 'foobar';
        $columns = ['id', 'price'];
        $dates = [];

        $filter = new FilterBetween($table, $columns, $dates);

        $key = 'other.price';
        $value = '1';
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->never();
        $this->assertFalse($filter->filter($query, $key, $value));
    }
}
