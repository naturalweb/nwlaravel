<?php

namespace Tests\Repositories\Criterias\Filters;

use Mockery as m;
use Tests\TestCase;
use NwLaravel\Repositories\Criterias\Filters\FilterDate;
use NwLaravel\Repositories\Criterias\Filters\FilterInterface;

class FilterDateTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $config = m::mock('config');
        $config->shouldReceive('get')->with('nwlaravel.date_format', null)->andReturn('d/m/Y');
        $this->app->instance('config', $config);
    }

    public function testFilterImplements()
    {
        $filter = new FilterDate('>');
        $this->assertInstanceOf(FilterInterface::class, $filter);
        $this->assertAttributeEquals('>', 'operator', $filter);
        $this->assertAttributeEquals(null, 'grammar', $filter);
    }

    /**
     * @dataProvider providerDatesValid
     */
    public function testFilterValids($database, $format, $column, $operator, $date)
    {
        $filter = new FilterDate($operator);
        $grammar = m::mock("Illuminate\Database\Query\Grammars\\{$database}Grammar[]");
        $queryBuilder = m::mock('Illuminate\Database\Query\Builder');
        $queryBuilder->shouldReceive('getGrammar')->andReturn($grammar);
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('getQuery')->andReturn($queryBuilder);
        $query->shouldReceive('whereRaw')->once()->with($format, [$date->format('Y-m-d')])->andReturn($query);

        $this->assertTrue($filter->filter($query, $column, $date));
    }

    public function providerDatesValid()
    {
        $now = new \Datetime;

        return [
            ['MySql', "DATE(`other`.`created_at`) >= ?", 'other.created_at', '>=', $now],
            ['Postgres', "DATE_TRUNC('day', \"other\".\"created_at\") = ?", 'other.created_at', '=', $now],
            ['SQLite', "strftime('%Y-%m-%d', \"other\".\"created_at\") < ?", 'other.created_at', '<', $now],
            ['SqlServer', "CAST([other].[created_at] AS DATE) != ?", 'other.created_at', '!=', $now],
        ];
    }

    public function testFilterValueStringWithMysql()
    {
        $filter = new FilterDate('>=');
        $key = 'other.created_at';
        $value = '31/10/2015';

        $grammar = m::mock('Illuminate\Database\Query\Grammars\MySqlGrammar[]');
        $queryBuilder = m::mock('Illuminate\Database\Query\Builder');
        $queryBuilder->shouldReceive('getGrammar')->andReturn($grammar);
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('getQuery')->andReturn($queryBuilder);
        $query->shouldReceive('whereRaw')->once()->with("DATE(`other`.`created_at`) >= ?", ['2015-10-31'])->andReturn($query);
        $this->assertTrue($filter->filter($query, $key, $value));
    }

    public function testFilteNullValueShouldExecWhereNull()
    {
        $filter = new FilterDate;
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('whereRaw')->never();
        $query->shouldReceive('whereNull')->with('foobar')->andReturn($query);

        $this->assertTrue($filter->filter($query, 'foobar', null));
    }

    public function testFilterValueInvalidShouldExecWhereNull()
    {
        $filter = new FilterDate;
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('whereRaw')->never();
        $query->shouldReceive('whereNull')->with('foobar')->andReturn($query);

        $this->assertTrue($filter->filter($query, 'foobar', new \stdClass));
    }

    public function testFilterValueInvalidDiffShouldExecWhereNotNull()
    {
        $filter = new FilterDate('!=');
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('whereRaw')->never();
        $query->shouldReceive('whereNotNull')->with('foobar')->andReturn($query);

        $this->assertTrue($filter->filter($query, 'foobar', new \stdClass));
    }

    public function testFilterEmptyShouldReceiveFalse()
    {
        $filter = new FilterDate;
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('whereRaw')->never();
        $query->shouldReceive('whereNull')->never();
        $this->assertFalse($filter->filter($query, 'foobar', ''));
    }
}
