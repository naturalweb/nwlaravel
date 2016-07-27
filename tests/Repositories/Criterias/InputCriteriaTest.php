<?php

namespace Tests\Repositories\Criterias;

use Mockery as m;
use Tests\TestCase;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use NwLaravel\Repositories\Criterias\InputCriteria;
use Illuminate\Support\Facades\DB;

class InputCriteriaTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->config = m::mock('config');
        $this->config->shouldReceive('get')->with('repository.criteria.params.search', null)->andReturn('search');
        $this->config->shouldReceive('get')->with('repository.criteria.params.orderBy', null)->andReturn('field2');
        $this->config->shouldReceive('get')->with('repository.criteria.params.sortedBy', null)->andReturn('DESC');
        $this->config->shouldReceive('get')->with('nwlaravel.date_format', null)->andReturn('d/m/Y');
        $this->app->instance('config', $this->config);

        $grammar = m::mock('Illuminate\Database\Grammar');
        $grammar->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
        DB::shouldReceive('getQueryGrammar')->andReturn($grammar);
    }

    public function testImplementsInstanceOf()
    {
        $input = ['foo' => 'bar'];

        $criteria = new InputCriteria($input);

        $this->assertAttributeEquals($input, 'input', $criteria);
    }

    public function testAddColumns()
    {
        $input = ['foo' => 'bar'];
        $columns = ['id', 'name', 'status'];

        $criteria = new InputCriteria($input);

        $this->assertAttributeEquals([], 'columns', $criteria);
        $criteria->addColumns($columns);

        $this->assertAttributeEquals($columns, 'columns', $criteria);
    }

    public function testApplyThrowException()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        $input = [['foo','-','bar']];
        $criteria = new InputCriteria($input);

        $query = '';
        $criteria->apply($query);
    }

    public function testApplyWhereAll()
    {
        $fieldsSearchable = ['field1' => 'like', 'field2'];

        $closure = function () {
            return '';
        };
        $expression = m::mock('Illuminate\Database\Query\Expression');
        $search = 'busca';

        $grammar = m::mock('Illuminate\Database\Query\Grammars\MySqlGrammar[]');
        $queryBuilder = m::mock('Illuminate\Database\Query\Builder');
        $queryBuilder->shouldReceive('getGrammar')->once()->andReturn($grammar);

        $model = new StubModel;

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('getModel')->once()->andReturn($model);
        $query->shouldReceive('getQuery')->once()->andReturn($queryBuilder);
        $query->shouldReceive('example')->once()->ordered()->with('new-value')->andReturn($query);
        $query->shouldReceive('where')->once()->ordered()->with($closure)->andReturn($query);
        $query->shouldReceive('whereRaw')->once()->ordered()->with($expression)->andReturn($query);
        $query->shouldReceive('whereRaw')->once()->ordered()->with('column > ?', [1])->andReturn($query);
        $query->shouldReceive('whereIn')->once()->ordered()->with('stub_table.field1', ['value1', 'value2'])->andReturn($query);
        $query->shouldReceive('whereNotIn')->once()->ordered()->with('stub_table.field1', ['value1', 'value2'])->andReturn($query);
        $query->shouldReceive('where')->once()->ordered()->with('stub_table.field2', '=', 'foobar')->andReturn($query);
        $query->shouldReceive('whereRaw')->once()->ordered()->with("DATE_FORMAT(`stub_table`.`field_date`, '%Y-%m-%d') = ?", ['2016-12-04'])->andReturn($query);
        $query->shouldReceive('whereRaw')->once()->ordered()->with("DATE_FORMAT(`stub_table`.`field_date`, '%Y-%m-%d') > ?", ['2015-11-27'])->andReturn($query);
        $query->shouldReceive('whereNull')->once()->ordered()->with("stub_table.field_date")->andReturn($query);
        $query->shouldReceive('whereNotNull')->once()->ordered()->with("stub_table.field_date")->andReturn($query);
        $query->shouldReceive('whereNull')->once()->ordered()->with("table.field3")->andReturn($query);
        $query->shouldReceive('whereNotNull')->once()->ordered()->with("stub_table.field3")->andReturn($query);
        $query->shouldReceive('where')->once()->ordered()->with('stub_table.field1', '<', 'valor3')->andReturn($query);
        $query->shouldReceive('where')->once()->ordered()->with('foo.field3', '>=', 10)->andReturn($query);
        $query->shouldReceive('where')->once()->ordered()->with('foo.field3', '<=', 50)->andReturn($query);
        $query->shouldReceive('whereRaw')->once()->ordered()->with("DATE_FORMAT(`stub_table`.`field_date`, '%Y-%m-%d') >= ?", ['2000-12-01'])->andReturn($query);
        $query->shouldReceive('whereRaw')->once()->ordered()->with("DATE_FORMAT(`stub_table`.`field_date`, '%Y-%m-%d') <= ?", ['2000-12-31'])->andReturn($query);

        $newQuery = m::mock('Illuminate\Database\Query\Builder');
        $newQuery->shouldReceive('orWhere')->once()->ordered()->with('stub_table.field1', 'like', "%{$search}%")->andReturn($query);
        $newQuery->shouldReceive('orWhere')->once()->ordered()->with('stub_table.field2', '=', "{$search}")->andReturn($query);

        $query->shouldReceive('where')
            ->once()
            ->ordered()
            ->andReturnUsing(function ($test) use ($newQuery, $query) {
                $test($newQuery);
                return $query;
            });

        $query->shouldReceive('orderBy')->once()->ordered()->with('field2', 'desc')->andReturn($query);

        $input = [
            'example' => 'new-value',
            $closure,
            $expression,
            'column > ?' => 1,
            'field1' => ['value1', 'value2'],
            ['field1','!=', ['value1', 'value2']],
            'field2' => 'foobar',
            'field-invalid' => 'bar',
            'field_date' => '04/12/2016',
            'field_date,>,27/11/2015',
            'field_date,=,invalid',
            'field_date,!=,invalid',
            'table.field3' => null,
            ['field3','!=', null],
            'field1,<,valor3',
            'foo.field3_ini' => 10,
            'foo.field3_fim' => 50,
            'field_date_ini' => '01/12/2000',
            'field_date_fim' => '31/12/2000',
            'search' => $search,
        ];

        $criteria = new InputCriteria($input);
        $criteria->setSearchables(['field1' => 'like']);

        $repo = m::mock('Prettus\Repository\Contracts\RepositoryInterface');
        $repo->shouldReceive('getFieldsSearchable')->once()->andReturn(['field2']);

        $this->assertEquals($query, $criteria->apply($query, $repo));
    }

    public function testApplyWithPostgres()
    {
        $grammar = m::mock('Illuminate\Database\Query\Grammars\PostgresGrammar[]');
        $queryBuilder = m::mock('Illuminate\Database\Query\Builder');
        $queryBuilder->shouldReceive('getGrammar')->once()->andReturn($grammar);

        $model = new StubModel;

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('getModel')->once()->andReturn($model);
        $query->shouldReceive('getQuery')->once()->andReturn($queryBuilder);
        $query->shouldReceive('whereRaw')
            ->once()
            ->ordered()
            ->with("DATE_TRUNC('day', \"stub_table\".\"field_date\") = ?", ['2016-12-04'])
            ->andReturn($query);

        $query->shouldReceive('orderBy')->once()->ordered()->with('field2', 'desc')->andReturn($query);

        $input = [
            'field_date' => '04/12/2016',
        ];

        $criteria = new InputCriteria($input);

        $this->assertEquals($query, $criteria->apply($query));
    }
    
    public function testApplyWithSQLite()
    {
        $grammar = m::mock('Illuminate\Database\Query\Grammars\SQLiteGrammar[]');
        $queryBuilder = m::mock('Illuminate\Database\Query\Builder');
        $queryBuilder->shouldReceive('getGrammar')->once()->andReturn($grammar);

        $model = new StubModel;

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('getModel')->once()->andReturn($model);
        $query->shouldReceive('getQuery')->once()->andReturn($queryBuilder);
        $query->shouldReceive('whereRaw')->once()->ordered()->with("strftime('%Y-%m-%d', \"stub_table\".\"field_date\") = ?", ['2016-12-04'])->andReturn($query);

        $query->shouldReceive('orderBy')->once()->ordered()->with('field2', 'desc')->andReturn($query);

        $input = [
            'field_date' => '04/12/2016',
        ];

        $criteria = new InputCriteria($input);

        $this->assertEquals($query, $criteria->apply($query));
    }

    public function testApplyWithSqlServer()
    {
        $grammar = m::mock('Illuminate\Database\Query\Grammars\SqlServerGrammar[]');
        $queryBuilder = m::mock('Illuminate\Database\Query\Builder');
        $queryBuilder->shouldReceive('getGrammar')->once()->andReturn($grammar);

        $model = new StubModel;

        $query = m::mock('Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('getModel')->once()->andReturn($model);
        $query->shouldReceive('getQuery')->once()->andReturn($queryBuilder);
        $query->shouldReceive('whereRaw')->once()->ordered()->with("CAST([stub_table].[field_date] AS DATE) = ?", ['2016-12-04'])->andReturn($query);

        $query->shouldReceive('orderBy')->once()->ordered()->with('field2', 'desc')->andReturn($query);

        $input = [
            'field_date' => '04/12/2016',
        ];

        $criteria = new InputCriteria($input);

        $this->assertEquals($query, $criteria->apply($query));
    }
}
