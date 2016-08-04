<?php

namespace Tests\Repositories\Eloquent;

use Mockery as m;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        $this->app->instance(Model::class, $this->model);

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

        $criteria = m::mock(InputCriteria::class);
        $criteria->shouldReceive('apply')->once()->with($this->model, $repo)->andReturn($this->model);
        $this->app->instance(InputCriteria::class, $criteria);

        $repo->shouldReceive('orderBy')->once()->with('name DESC')->andReturn($repo);
        $repo->shouldReceive('paginate')->once()->with(20)->andReturn($result);

        $this->assertEquals($result, $repo->search($input, 'name DESC', 20));
    }

    public function testSearchAll()
    {
        $result = ['result-all'];
        $input = ['foo', 'bar' => 'baz'];

        $query = m::mock(Builder::class);
        $query->shouldReceive('limit')->with(100)->andReturn($result);

        $repo = m::mock(StubAbstractRepository::class.'[skipPresenter, orderBy, getQuery]', [$this->app]);
        $repo->shouldReceive('skipPresenter')->once()->with(true)->andReturn($repo);
        $repo->shouldReceive('orderBy')->once()->with('name DESC')->andReturn($repo);
        $repo->shouldReceive('getQuery')->once()->andReturn($query);

        $criteria = m::mock(InputCriteria::class);
        $criteria->shouldReceive('apply')->once()->with($this->model, $repo)->andReturn($this->model);
        $this->app->instance(InputCriteria::class, $criteria);

        $resultset = m::mock(BuilderResultset::class);
        $this->app->instance(BuilderResultset::class, $resultset);

        $this->assertEquals($resultset, $repo->searchAll($input, 'name DESC', 100, true));
    }

    public function testWhere()
    {
        $repo = new StubAbstractRepository($this->app);

        $this->model
            ->shouldReceive('where')
            ->once()
            ->with('foobar', '>', 'baz', 'or')
            ->andReturn($repo);

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

    public function testReorderWithSqlite()
    {
        $this->setExpectedException('RuntimeException');

        $conn = m::mock('Illuminate\Database\SQLiteConnection');
        $this->model
            ->shouldReceive('getConnection')
            ->once()
            ->andReturn($conn);

        $repo = new StubAbstractRepository($this->app);
        $repo->reorder([]);
    }

    public function testReorderWithMysql()
    {
        $conn = m::mock('Illuminate\Database\MySqlConnection');
        $conn->shouldReceive('statement')->once()->with("SET @rownum := 0");
        $conn->shouldReceive('raw')->once()->with("(@rownum := @rownum+1)")->andReturn('(@rownum := @rownum+1)');
        
        $this->model
            ->shouldReceive('getConnection')
            ->once()
            ->andReturn($conn);

        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('update')->once()->with(['ordem' => '(@rownum := @rownum+1)'])->andReturn(true);

        $repo = m::mock(StubAbstractRepository::class.'[whereInputCriteria, orderBy, getQuery]', [$this->app]);
        $repo->shouldReceive('whereInputCriteria')->once()->with(['where' => 'foo'])->andReturn($repo);
        $repo->shouldReceive('orderBy')->once()->with('ordem')->andReturn($repo);
        $repo->shouldReceive('getQuery')->once()->andReturn($query);
        
        $repo->reorder('ordem', ['where' => 'foo']);
    }

    public function testReorderWithPostgres()
    {
        $conn = m::mock('Illuminate\Database\PostgresConnection');
        $conn->shouldReceive('statement')->once()->with("CREATE TEMPORARY SEQUENCE rownum_seq");
        $conn->shouldReceive('raw')->once()->with("NETVAL('rownum_seq')")->andReturn("NETVAL('rownum_seq')");
        
        $this->model
            ->shouldReceive('getConnection')
            ->once()
            ->andReturn($conn);

        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('update')->once()->with(['ordem' => "NETVAL('rownum_seq')"])->andReturn(true);

        $repo = m::mock(StubAbstractRepository::class.'[whereInputCriteria, orderBy, getQuery]', [$this->app]);
        $repo->shouldReceive('whereInputCriteria')->once()->with(['where' => 'foo'])->andReturn($repo);
        $repo->shouldReceive('orderBy')->once()->with('ordem')->andReturn($repo);
        $repo->shouldReceive('getQuery')->once()->andReturn($query);
        
        $repo->reorder('ordem', ['where' => 'foo']);
    }

    public function testReorderWithSqlServer()
    {
        $conn = m::mock('Illuminate\Database\SqlServerConnection');
        $conn->shouldReceive('statement')->once()->with("DECLARE @rownum int; SET @rownum = 0");
        $conn->shouldReceive('raw')->once()->with("(@rownum = @rownum+1)")->andReturn("(@rownum = @rownum+1)");
        
        $this->model
            ->shouldReceive('getConnection')
            ->once()
            ->andReturn($conn);

        $query = m::mock('Eloquent\Builder');
        $query->shouldReceive('update')->once()->with(['ordem' => "(@rownum = @rownum+1)"])->andReturn(true);

        $repo = m::mock(StubAbstractRepository::class.'[whereInputCriteria, orderBy, getQuery]', [$this->app]);
        $repo->shouldReceive('whereInputCriteria')->once()->with(['where' => 'foo'])->andReturn($repo);
        $repo->shouldReceive('orderBy')->once()->with('ordem')->andReturn($repo);
        $repo->shouldReceive('getQuery')->once()->andReturn($query);
        
        $repo->reorder('ordem', ['where' => 'foo']);
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

        $criteria = m::mock(InputCriteria::class);
        $criteria->shouldReceive('apply')->once()->with($this->model, $repo)->andReturn($this->model);
        $this->app->instance(InputCriteria::class, $criteria);

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

        $criteria = m::mock(InputCriteria::class);
        $criteria->shouldReceive('apply')->once()->with($this->model, $repo)->andReturn($this->model);
        $this->app->instance(InputCriteria::class, $criteria);

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

        $criteria = m::mock(InputCriteria::class);
        $criteria->shouldReceive('apply')->once()->with($this->model, $repo)->andReturn($this->model);
        $this->app->instance(InputCriteria::class, $criteria);

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

        $criteria = m::mock(InputCriteria::class);
        $criteria->shouldReceive('apply')->once()->with($this->model, $repo)->andReturn($this->model);
        $this->app->instance(InputCriteria::class, $criteria);

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

        $criteria = m::mock(InputCriteria::class);
        $criteria->shouldReceive('apply')->once()->with($this->model, $repo)->andReturn($this->model);
        $this->app->instance(InputCriteria::class, $criteria);

        $this->assertEquals(10.5, $repo->avg('fieldavg', $input));
    }

    public function testOrderBy()
    {
        $lists = ['1' => 'Foo', '2' => 'Bar'];

        $this->model
            ->shouldReceive('orderBy')
            ->once()
            ->with('name', 'ASC')
            ->andReturn($this->model);

        $repo = new StubAbstractRepository($this->app);

        $this->assertEquals($repo, $repo->orderBy('name ASC'));
    }

    public function testOrderByWithNewQuery()
    {
        $builder = m::mock(Builder::class);

        $this->model
            ->shouldReceive('newQuery')
            ->once()
            ->andReturn($builder);

        $repo = new StubAbstractRepository($this->app);

        $this->assertEquals($builder, $repo->getQuery());
    }

    public function testOrderByWithBuilder()
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

    public function testWhereInputCriteria()
    {
        $input = ['foo', 'bar' => 'baz'];

        $repo = new StubAbstractRepository($this->app);

        $criteria = m::mock(InputCriteria::class);
        $criteria->shouldReceive('apply')->once()->with($this->model, $repo)->andReturn($this->model);
        $this->app->instance(InputCriteria::class, $criteria);

        $this->assertEquals($repo, $repo->whereInputCriteria($input));
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
        $this->model->shouldReceive('newInstance')->once()->ordered()->andReturn($modelValid);

        $new_model = m::mock(Model::class);
        $new_model->shouldReceive('save')->once();
        $this->model->shouldReceive('newInstance')->once()->ordered()->with($attributes)->andReturn($new_model);

        $events = m::mock('events');
        $events->shouldReceive('fire')->once();
        $this->app->instance('events', $events);

        $repo = new StubAbstractRepository($this->app);
        $repo->makeValidator($validator);

        $this->assertEquals($new_model, $repo->create($input));
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
        $this->model->shouldReceive('newInstance')->once()->ordered()->andReturn($modelValid);

        $new_model = m::mock(Model::class);
        $new_model->shouldReceive('fill')->once()->with($attributes)->andReturn($new_model);
        $new_model->shouldReceive('save')->once();
        $this->model->shouldReceive('findOrFail')->once()->ordered()->with($id)->andReturn($new_model);

        $events = m::mock('events');
        $events->shouldReceive('fire')->once();
        $this->app->instance('events', $events);

        $repo = new StubAbstractRepository($this->app);
        $repo->makeValidator($validator);

        $this->assertEquals($new_model, $repo->update($input, $id));
    }
}

class StubAbstractRepository extends \NwLaravel\Repositories\Eloquent\AbstractRepository
{
    public function model()
    {
        return Model::class;
    }
}
