<?php

namespace Tests\Repositories\Eloquent;

use Mockery as m;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use NwLaravel\Repositories\Criterias\InputCriteria;
use NwLaravel\Resultset\BuilderResultset;
use Prettus\Validator\Contracts\ValidatorInterface;

class AbstractRepositoryTest extends TestCase
{
    protected $app;

    public function setUp()
    {
        parent::setUp();
        $this->model = m::mock(Model::class);
        $this->model->shouldReceive('getDates')->andReturn([]);
        $this->model->shouldReceive('getTable')->andReturn('foo');
        $this->model->shouldReceive('newQuery')->andReturn($this->model);
        $this->app->instance(Model::class, $this->model);

        $this->config = m::mock('config');
        $this->config->shouldReceive('get')->andReturn('');
        $this->app->instance('config', $this->config);
    }

    public function testImplementsInstanceOf()
    {
        $repo = new StubAbstractRepository($this->app);

        $this->assertInstanceOf('NwLaravel\Repositories\RepositoryInterface', $repo);
        $this->assertInstanceOf('Prettus\Repository\Eloquent\BaseRepository', $repo);
    }

    public function testSearch()
    {
        $result = ['result-paginate'];
        $input = ['foo', 'bar' => 'baz'];

        $repo = m::mock(StubAbstractRepository::class.'[orderBy, paginate]', [$this->app]);

        $repo->shouldReceive('orderBy')->once()->with('name DESC')->andReturn($repo);
        $repo->shouldReceive('paginate')->once()->with(20)->andReturn($result);

        $this->assertEquals($result, $repo->search($input, 'name DESC', 20));
    }

    public function testSearchAll()
    {
        $sql = 'select * from foobar ORDER BY name DESC LIMIT 100';
        $bindings = [];
        $statement = m::mock('PDOStatement');
        $statement->shouldReceive('execute')->once()->with($bindings)->andReturn(true);
        $pdo = m::mock('PDO');
        $pdo->shouldReceive('prepare')->once()->with($sql)->andReturn($statement);
        $conn = m::mock('Illuminate\Database\Connection');
        $conn->shouldReceive('getPdo')->once()->andReturn($pdo);
        $conn->shouldReceive('prepareBindings')->once()->with($bindings)->andReturn($bindings);
        $builder = m::mock('Illuminate\Database\Query\Builder');
        $builder->shouldReceive('toSql')->once()->andReturn($sql);
        $builder->shouldReceive('getBindings')->once()->andReturn($bindings);
        $builder->shouldReceive('getConnection')->once()->andReturn($conn);
        $prototype = m::mock('Illuminate\Database\Eloquent\Model[]');
        $this->model->shouldReceive('newFromBuilder')->once()->andReturn($prototype);

        $query = m::mock(Builder::class);
        $query->shouldReceive('toBase')->andReturn($builder);
        $query->shouldReceive('getModel')->once()->andReturn($this->model);
        $query->shouldReceive('limit')->with(100)->andReturn($query);

        $repo = m::mock(StubAbstractRepository::class.'[skipPresenter, orderBy, getQuery]', [$this->app]);
        $repo->shouldReceive('skipPresenter')->once()->with(true)->andReturn($repo);
        $repo->shouldReceive('orderBy')->once()->with('name DESC')->andReturn($repo);
        $repo->shouldReceive('getQuery')->once()->andReturn($query);

        $resultset = $repo->searchAll([], 'name DESC', 100, true);
        $this->assertInstanceOf(BuilderResultset::class, $resultset);
    }

    public function testWhere()
    {
        $repo = new StubAbstractRepository($this->app);

        $this->model
            ->shouldReceive('where')
            ->once()
            ->with('foobar', '>', 'baz', 'or')
            ->andReturn($this->model);

        $this->assertEquals($repo, $repo->where('foobar', '>', 'baz', 'or'));
    }

    public function testInnerJoin()
    {
        $repo = new StubAbstractRepository($this->app);

        $this->model
            ->shouldReceive('join')
            ->once()
            ->with('foo', 'bar', 'foo.id', '=', 'bar.foo_id')
            ->andReturn($repo);

        $this->assertEquals($repo, $repo->join('foo', 'bar', 'foo.id', '=', 'bar.foo_id'));
    }

    public function testLeftJoin()
    {
        $repo = new StubAbstractRepository($this->app);

        $this->model
            ->shouldReceive('leftJoin')
            ->once()
            ->with('bar', 'foo', 'foo.bar_id', '=', 'bar.id')
            ->andReturn($repo);

        $this->assertEquals($repo, $repo->leftJoin('bar', 'foo', 'foo.bar_id', '=', 'bar.id'));
    }

    public function testRightJoin()
    {
        $repo = new StubAbstractRepository($this->app);

        $this->model
            ->shouldReceive('rightJoin')
            ->once()
            ->with('bar', 'foo', 'foo.id', '=', 'bar.foo_id')
            ->andReturn($repo);

        $this->assertEquals($repo, $repo->rightJoin('bar', 'foo', 'foo.id', '=', 'bar.foo_id'));
    }

    public function testOrWhere()
    {
        $repo = new StubAbstractRepository($this->app);

        $this->model
            ->shouldReceive('orWhere')
            ->once()
            ->with('foobar', 'baz')
            ->andReturn($repo);

        $this->assertEquals($repo, $repo->orWhere('foobar', 'baz'));
    }

    public function testGroupBy()
    {
        $repo = new StubAbstractRepository($this->app);

        $this->model
            ->shouldReceive('groupBy')
            ->once()
            ->with('bazz', 'fooo', 'other')
            ->andReturn($repo);

        $this->assertEquals($repo, $repo->groupBy('bazz', 'fooo', 'other'));
    }

    public function testMethodNoEnabled()
    {
        $this->setExpectedException('BadMethodCallException');

        $this->model
            ->shouldReceive('from')
            ->never();

        $repo = new StubAbstractRepository($this->app);
        $repo->from('newtable');
    }

    public function testPluck()
    {
        $lists = ['1' => 'Foo', '2' => 'Bar'];

        $this->model
            ->shouldReceive('pluck')
            ->once()
            ->with('name', 'id')
            ->andReturn($lists);

        $repo = new StubAbstractRepository($this->app);

        $this->assertEquals($lists, $repo->pluck('name', 'id'));

    }

    public function testCount()
    {
        $this->model
            ->shouldReceive('count')
            ->once()
            ->withNoArgs()
            ->andReturn(33);

        $input = ['foo', 'bar' => 'baz'];

        $repo = new StubAbstractRepository($this->app);

        $this->assertEquals(33, $repo->count($input));
    }

    public function testMax()
    {
        $this->model
            ->shouldReceive('max')
            ->once()
            ->with('fieldmax')
            ->andReturn(52);

        $input = ['foo', 'bar' => 'baz'];

        $repo = new StubAbstractRepository($this->app);

        $this->assertEquals(52, $repo->max('fieldmax', $input));
    }

    public function testMin()
    {
        $this->model
            ->shouldReceive('min')
            ->once()
            ->with('fieldmin')
            ->andReturn(1);

        $input = ['foo', 'bar' => 'baz'];

        $repo = new StubAbstractRepository($this->app);

        $this->assertEquals(1, $repo->min('fieldmin', $input));
    }

    public function testSum()
    {
        $this->model
            ->shouldReceive('sum')
            ->once()
            ->with('fieldsum')
            ->andReturn(166);

        $input = ['foo', 'bar' => 'baz'];

        $repo = new StubAbstractRepository($this->app);

        $this->assertEquals(166, $repo->sum('fieldsum', $input));
    }

    public function testAvg()
    {
        $this->model
            ->shouldReceive('avg')
            ->once()
            ->with('fieldavg')
            ->andReturn(10.5);

        $input = ['foo', 'bar' => 'baz'];

        $repo = new StubAbstractRepository($this->app);

        $this->assertEquals(10.5, $repo->avg('fieldavg', $input));
    }

    public function testOrderBy()
    {
        $lists = ['1' => 'Foo', '2' => 'Bar'];

        $this->model
            ->shouldReceive('orderBy')
            ->once()
            ->with('name', 'DESC')
            ->andReturn($this->model);

        $repo = new StubAbstractRepository($this->app);

        $this->assertEquals($repo, $repo->orderBy('name DESC'));
    }

    public function testOrderByOnlyName()
    {
        $lists = ['1' => 'Foo', '2' => 'Bar'];

        $this->model
            ->shouldReceive('orderBy')
            ->once()
            ->with('name', 'ASC')
            ->andReturn($this->model);

        $repo = new StubAbstractRepository($this->app);

        $this->assertEquals($repo, $repo->orderBy('name'));
    }

    public function testOrderBySortInvalid()
    {
        $lists = ['1' => 'Foo', '2' => 'Bar'];

        $this->model
            ->shouldReceive('orderBy')
            ->once()
            ->with('name', 'ASC')
            ->andReturn($this->model);

        $repo = new StubAbstractRepository($this->app);

        $this->assertEquals($repo, $repo->orderBy('name foo'));
    }

    public function providerRandom()
    {
        return [
            ['RAND()', 'MySqlGrammar'],
            ['RAND()', 'SqlServerGrammar'],
            ['RANDOM()', 'PostgresGrammar'],
            ['RANDOM()', 'SQLiteGrammar'],
        ];
    }

    /**
     * @dataProvider providerRandom
     */
    public function testRandom($random, $classGrammar)
    {
        $model = $this->model;
        $test = $this;

        $grammar = m::mock("Illuminate\Database\Query\Grammars\\{$classGrammar}[]");
        $conn = m::mock("Illuminate\Database\Connection");
        $conn->shouldReceive('getQueryGrammar')->once()->andReturn($grammar);
        
        $model->shouldReceive('getConnection')->once()->andReturn($conn);

        $model->shouldReceive('orderBy')
            ->once()
            ->andReturnUsing(function ($expression) use ($model, $test, $random) {
                $test->assertInstanceOf(Expression::class, $expression);
                $test->assertEquals($random, $expression->getValue());
                return $this;
            });

        $repo = new StubAbstractRepository($this->app);

        $this->assertEquals($repo, $repo->random());
    }

    public function testGetQuery()
    {
        $builder = m::mock(Builder::class);

        $repo = new StubAbstractRepository($this->app);

        $this->assertEquals($this->model, $repo->getQuery());
    }

    public function testGetQueryWithBuilder()
    {
        $builder = m::mock(Builder::class);

        $this->model
            ->shouldReceive('with')
            ->once()
            ->andReturn($builder);

        $repo = new StubAbstractRepository($this->app);
        $repo->with('test');

        $this->assertEquals($builder, $repo->getQuery());
    }

    public function testCreate()
    {
        $toArray = ['foo' => 'bar'];
        $input = ['foo' => '', 'test' => 'barrrr'];
        $attributes = ['foo' => 'bar', 'test' => 'barrrr'];

        $validator = m::mock('Prettus\Validator\LaravelValidator');
        $validator->shouldReceive('with')->with($attributes)->andReturn($validator);
        $validator->shouldReceive('passesOrFail')->with(ValidatorInterface::RULE_CREATE);

        $modelValid = m::mock(Model::class);
        $modelValid->shouldReceive('forceFill')->once()->with($input)->andReturn($modelValid);
        $modelValid->shouldReceive('toArray')->once()->andReturn($toArray);
        $modelValid->shouldReceive('save')->once();
        $this->model->shouldReceive('newModelInstance')->twice()->ordered()->andReturn($modelValid);

        $repo = new StubAbstractRepository($this->app);
        $repo->makeValidator($validator);

        $events = m::mock('events');
        $events->shouldReceive('dispatch')->once();
        $this->app->instance('events', $events);

        $this->assertEquals($modelValid, $repo->create($input));
    }

    public function testUpdate()
    {
        $toArray = ['foo' => 'bar'];
        $input = ['foo' => '', 'test' => 'barrrr'];
        $attributes = ['foo' => 'bar', 'test' => 'barrrr'];
        $id = '10';

        $validator = m::mock('Prettus\Validator\LaravelValidator');
        $validator->shouldReceive('with')->with($attributes)->andReturn($validator);
        $validator->shouldReceive('setId')->with($id)->andReturn($validator);
        $validator->shouldReceive('passesOrFail')->with(ValidatorInterface::RULE_UPDATE);

        $modelValid = m::mock(Model::class);
        $modelValid->shouldReceive('forceFill')->once()->with($input)->andReturn($modelValid);
        $modelValid->shouldReceive('toArray')->once()->andReturn($toArray);
        $this->model->shouldReceive('newModelInstance')->once()->ordered()->andReturn($modelValid);

        $new_model = m::mock(Model::class);
        $new_model->shouldReceive('fill')->once()->andReturn($new_model);
        $new_model->shouldReceive('save')->once();
        $this->model->shouldReceive('findOrFail')->once()->ordered()->with($id)->andReturn($new_model);

        $repo = new StubAbstractRepository($this->app);
        $repo->makeValidator($validator);

        $events = m::mock('events');
        $events->shouldReceive('dispatch')->once();
        $this->app->instance('events', $events);

        $this->assertEquals($new_model, $repo->update($input, $id));
    }

    public function testDeleteWhere()
    {
        $where = ['search' => 'busca_registros'];
        $attributes = ['foo' => 'bar', 'test' => 'barrrr'];

        $this->model->shouldReceive('delete')->once()->andReturn(true);

        $repo = m::mock(StubAbstractRepository::class.'[whereInputCriteria]', [$this->app]);
        $repo->shouldReceive('whereInputCriteria')->once()->with($where)->andReturn($repo);

        $events = m::mock('events');
        $events->shouldReceive('dispatch')->once();
        $this->app->instance('events', $events);

        $this->assertTrue($repo->deleteWhere($where));
    }

    public function testUpdateWhere()
    {
        $where = ['search' => 'busca_registros'];
        $attributes = ['foo' => 'bar', 'test' => 'barrrr'];

        $this->model->shouldReceive('update')->once()->with($attributes)->andReturn(true);

        $repo = m::mock(StubAbstractRepository::class.'[whereInputCriteria]', [$this->app]);
        $repo->shouldReceive('whereInputCriteria')->once()->with($where)->andReturn($repo);

        $events = m::mock('events');
        $events->shouldReceive('dispatch')->once();
        $this->app->instance('events', $events);

        $this->assertTrue($repo->updateWhere($attributes, $where));
    }
}

class StubAbstractRepository extends \NwLaravel\Repositories\Eloquent\AbstractRepository
{
    public function model()
    {
        return Model::class;
    }
}
