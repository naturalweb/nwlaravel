<?php

namespace Tests\Repositories\Criterias\Filters;

use Mockery as m;
use Tests\TestCase;
use NwLaravel\Repositories\Criterias\Filters\FilterSearch;
use NwLaravel\Repositories\Criterias\Filters\FilterInterface;

class FilterSearchTest extends TestCase
{
    public function testFilterImplements()
    {
        $nameSearchable = 'search';
        $searchables = ['name' => 'like', 'email' => '='];
        $table = 'foobar';

        $filter = new FilterSearch($nameSearchable, $searchables, $table);
        $this->assertInstanceOf(FilterInterface::class, $filter);
        $this->assertAttributeEquals($nameSearchable, 'nameSearchable', $filter);
        $this->assertAttributeEquals($searchables, 'searchables', $filter);
        $this->assertAttributeEquals($table, 'table', $filter);
    }

    public function testFilterValidSearch()
    {
        $nameSearchable = 'search';
        $searchables = ['name' => 'like', 'email'];
        $table = 'foobar';

        $filter = new FilterSearch($nameSearchable, $searchables, $table);

        $key = 'search';
        $value = 'maria';
        $query = m::mock('Eloquent\Builder');

        $query->shouldReceive('where')->once()->andReturnUsing(function($callback) use ($query){
            return $callback($query);
        });
        $query->shouldReceive('orWhere')->once()->with('foobar.name', 'like', '%maria%')->andReturn($query);
        $query->shouldReceive('orWhere')->once()->with('foobar.email', '=', 'maria')->andReturn($query);

        $this->assertTrue($filter->filter($query, $key, $value));
    }

    public function testFilterSearchValueEmpty()
    {
        $nameSearchable = 'search';
        $searchables = ['name' => 'like', 'email'];
        $table = 'foobar';

        $filter = new FilterSearch($nameSearchable, $searchables, $table);

        $key = 'search';
        $value = '';
        $query = m::mock('Eloquent\Builder');

        $query->shouldReceive('where')->once()->andReturnUsing(function($callback) use ($query){
            return $callback($query);
        });
        $query->shouldReceive('orWhere')->never();

        $this->assertTrue($filter->filter($query, $key, $value));
    }

    public function testFilterKeyInvalid()
    {
        $nameSearchable = 'search';
        $searchables = ['name' => 'like', 'email' => '='];
        $table = 'foobar';

        $filter = new FilterSearch($nameSearchable, $searchables, $table);

        $key = 'other';
        $value = 'maria';
        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('where')->never();
        $query->shouldReceive('orWhere')->never();
        $this->assertFalse($filter->filter($query, $key, $value));
    }
}
