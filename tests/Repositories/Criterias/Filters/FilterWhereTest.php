<?php

namespace Tests\Repositories\Criterias\Filters;

use Mockery as m;
use Tests\TestCase;
use NwLaravel\Repositories\Criterias\Filters\FilterWhere;
use NwLaravel\Repositories\Criterias\Filters\FilterInterface;

class FilterWhereTest extends TestCase
{
    public function testFilterImplements()
    {
        $table = 'foobar';
        $columns = ['id', 'name'];
        $dates = ['created_at'];
        $operator = '<>';

        $filter = new FilterWhere($table, $columns, $dates, $operator);
        $this->assertInstanceOf(FilterInterface::class, $filter);
        $this->assertAttributeEquals($table, 'table', $filter);
        $this->assertAttributeEquals($columns, 'columns', $filter);
        $this->assertAttributeEquals($dates, 'dates', $filter);
        $this->assertAttributeEquals($operator, 'operator', $filter);
    }

    public function testOperatorInvalidShouldThrowException()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        $table = 'foobar';
        $columns = ['id', 'name'];
        $dates = ['created_at'];
        $operator = '#';
        $filter = new FilterWhere($table, $columns, $dates, $operator);

        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->never();
        $query->shouldReceive('whereNull')->with('foobar')->andReturn($query);

        $this->assertFalse($filter->filter($query, 'name', 'foo'));
    }

    public function testWithBidding()
    {
        $table = 'foobar';
        $columns = ['id', 'name'];
        $dates = ['created_at'];
        $operator = '>';
        $filter = new FilterWhere($table, $columns, $dates, $operator);

        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->never();
        $query->shouldReceive('whereRaw')->with('name = ?', ['foo'])->andReturn($query);

        $this->assertTrue($filter->filter($query, 'name = ?', 'foo'));
    }

    public function testWhereNull()
    {
        $table = 'foobar';
        $columns = ['id', 'name'];
        $dates = ['created_at'];
        $operator = '=';
        $filter = new FilterWhere($table, $columns, $dates, $operator);

        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->never();
        $query->shouldReceive('whereNull')->with('foobar.name')->andReturn($query);

        $this->assertTrue($filter->filter($query, 'name', null));
    }

    public function testWhereNotNull()
    {
        $table = 'foobar';
        $columns = ['id', 'name'];
        $dates = ['created_at'];
        $operator = '<>';
        $filter = new FilterWhere($table, $columns, $dates, $operator);

        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->never();
        $query->shouldReceive('whereNotNull')->with('foobar.name')->andReturn($query);

        $this->assertTrue($filter->filter($query, 'name', null));
    }

    public function testWhereIn()
    {
        $table = 'foobar';
        $columns = ['id', 'name'];
        $dates = ['created_at'];
        $operator = '=';
        $filter = new FilterWhere($table, $columns, $dates, $operator);

        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->never();
        $query->shouldReceive('whereIn')->with('foobar.name', ['bar', 'bazz'])->andReturn($query);

        $this->assertTrue($filter->filter($query, 'name', ['bar', 'bazz']));
    }

    public function testWhereNotIn()
    {
        $table = 'foobar';
        $columns = ['id', 'name'];
        $dates = ['created_at'];
        $operator = '!=';
        $filter = new FilterWhere($table, $columns, $dates, $operator);

        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->never();
        $query->shouldReceive('whereNotIn')->with('foobar.name', ['bar', 'bazz'])->andReturn($query);

        $this->assertTrue($filter->filter($query, 'name', ['bar', 'bazz']));
    }

    public function testWhereDirect()
    {
        $table = '';
        $columns = ['id', 'name'];
        $dates = ['created_at'];
        $operator = '=';
        $filter = new FilterWhere($table, $columns, $dates, $operator);

        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->with('other.name', '=', 'foo')->andReturn($query);

        $this->assertTrue($filter->filter($query, 'other.name', 'foo'));
    }

    public function testFilterColumnDate()
    {
        $table = 'foobar';
        $columns = ['id', 'created_at'];
        $dates = ['created_at'];
        $operator = '=';
        $filter = new FilterWhere($table, $columns, $dates, $operator);

        $key = 'other.created_at';
        $value = '2015-10-31';

        $grammar = m::mock('Illuminate\Database\Query\Grammars\MySqlGrammar[]');
        $queryBuilder = m::mock('Illuminate\Database\Query\Builder');
        $queryBuilder->shouldReceive('getGrammar')->andReturn($grammar);
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('getQuery')->andReturn($queryBuilder);
        $query->shouldReceive('whereRaw')->once()->with("DATE(`other`.`created_at`) = ?", ['2015-10-31'])->andReturn($query);
        $this->assertTrue($filter->filter($query, $key, $value));
    }

    public function testFilterBetween()
    {
        $table = 'foobar';
        $columns = ['id', 'price'];
        $dates = [];
        $operator = '>';
        $filter = new FilterWhere($table, $columns, $dates, $operator);

        $key = 'price_fim';
        $value = 10;

        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->once()->with("foobar.price", "<=", 10)->andReturn($query);
        $this->assertTrue($filter->filter($query, $key, $value));
    }
}
