<?php
namespace Tests\Entities;

use Tests\TestCase;
use Mockery as m;
use NwLaravel\Entities\AbstractEntity;
use Prettus\Repository\Contracts\Presentable;
use Prettus\Repository\Traits\PresentableTrait;
use Prettus\Repository\Contracts\PresenterInterface;
use Illuminate\Support\Facades\DB;

class AbstractEntityTest extends TestCase
{
    public function testConstructInstanceOf()
    {
        $atual = m::mock(AbstractEntity::class.'[]');

        $this->assertInstanceOf(Presentable::class, $atual);
    }

    public function testHasAndSetPresenter()
    {
        $entity = m::mock(AbstractEntity::class.'[]');
        $this->assertFalse($entity->hasPresenter());

        $presenter = m::mock(PresenterInterface::class);
        $this->assertEquals($entity, $entity->setPresenter($presenter));

        $this->assertTrue($entity->hasPresenter());
    }

    public function testNoPresenter()
    {
        $entity = m::mock(AbstractEntity::class.'[]');

        $this->assertEquals($entity, $entity->presenter());
    }

    public function testPresenter()
    {
        $data = ['foo', 'bar'];

        $presenter = m::mock(PresenterInterface::class);
        $presenter->shouldReceive('present')->once()->andReturn($data);

        $entity = m::mock(AbstractEntity::class.'[]');
        $entity->setPresenter($presenter);

        $this->assertEquals($data, $entity->presenter());
    }

    public function testMethodSetAttribute()
    {
        $entity = m::mock(AbstractEntity::class.'[]');

        $entity->setAttribute('fieldEspacos', '  valor espacos  ');
        $entity->setAttribute('fieldNumStr', '0');
        $entity->setAttribute('fieldNumero', 0);
        $entity->setAttribute('fieldEmpty', '');
        $entity->setAttribute('fieldOK', 'ok');

        $attributes = [
            'fieldEspacos' => 'valor espacos',
            'fieldNumStr' => '0',
            'fieldNumero' => 0,
            'fieldEmpty' => null,
            'fieldOK' => 'ok',
        ];
        $this->assertAttributeSame($attributes, 'attributes', $entity);
    }

    public function testMethodGetRawAttribute()
    {
        $entity = m::mock(AbstractEntity::class.'[]');
        $entity->setRawAttributes(['foobar' => 'ValorPuro']);

        $this->assertEquals('ValorPuro', $entity->getRawAttribute('foobar'));
    }

    public function testMethodsColumnsAndIsColumn()
    {
        $columns = ['name', 'status'];

        $schema = m::mock('Illuminate\Database\Schema\Builder');
        $schema->shouldReceive('getColumnListing')->once()->with('table_foobar')->andReturn($columns);

        $connection = m::mock('Illuminate\Database\Connection');
        $connection->shouldReceive('getSchemaBuilder')->once()->andReturn($schema);

        $entity = m::mock(AbstractEntity::class.'[getTable,getConnection]');
        $entity->shouldReceive('getTable')->once()->andReturn('table_foobar');
        $entity->shouldReceive('getConnection')->once()->andReturn($connection);

        $this->assertEquals(['name', 'status', '_id', 'id'], $entity->columns());
        $this->assertTrue($entity->isColumn('name'));
        $this->assertFalse($entity->isColumn('nascimento'));
    }

    public function testMethodLastInsertId()
    {
        $columns = ['id', 'name', 'status'];

        $pdo = m::mock('Illuminate\Database\Schema\Builder');
        $pdo->shouldReceive('lastInsertId')->once()->andReturn(23);

        $connection = m::mock('Illuminate\Database\Connection');
        $connection->shouldReceive('getPdo')->once()->andReturn($pdo);

        $entity = m::mock(AbstractEntity::class.'[getConnection]');
        $entity->shouldReceive('getConnection')->once()->andReturn($connection);

        $this->assertEquals(23, $entity->lastInsertId());
    }

    public function testMethodFrontDateTime()
    {
        $grammar = m::mock('Illuminate\Database\Grammar');
        $grammar->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
        DB::shouldReceive('getQueryGrammar')->andReturn($grammar);

        $this->config = m::mock('config');
        $this->config->shouldReceive('get')->with('nwlaravel.date_format', null)->andReturn('d/m/Y');
        $this->app->instance('config', $this->config);

        $entity = m::mock(AbstractEntity::class.'[]');
        $entity->setDateFormat('Y-m-d H:i:s');

        $data = new \DateTime('2015-12-25 14:05:39');
        $this->assertEquals('2015-12-25 14:05:39', $entity->fromDateTime($data));

        $data = '26/02/2015';
        $this->assertEquals('2015-02-26 00:00:00', $entity->fromDateTime($data));

        $data = '31/02/2016';
        $this->assertNull($entity->fromDateTime($data));
    }

    public function testMethodAsDateTime()
    {
        $grammar = m::mock('Illuminate\Database\Grammar');
        $grammar->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
        DB::shouldReceive('getQueryGrammar')->andReturn($grammar);
        
        $this->config = m::mock('config');
        $this->config->shouldReceive('get')->with('nwlaravel.date_format', null)->andReturn('d/m/Y');
        $this->app->instance('config', $this->config);

        $entity = m::mock(AbstractEntity::class.'[]');

        $data = '2015-12-25 14:05:39';
        $this->assertEquals(new \Carbon\Carbon('2015-12-25 14:05:39'), $entity->asDateTime($data));

        $data = '26/02/2015';
        $this->assertEquals('2015-02-26 00:00:00', $entity->asDateTime($data));

        $data = '31/02/2016';
        $this->assertNull($entity->asDateTime($data));
    }

    public function testScopeWhereCriteria()
    {
        $query = m::mock('query');

        $criteria = m::mock('NwLaravel\Repositories\Criterias\InputCriteria');
        $criteria->shouldReceive('apply')->once()->with($query)->andReturn($query);
        $this->app->instance('NwLaravel\Repositories\Criterias\InputCriteria', $criteria);

        $entity = m::mock(AbstractEntity::class.'[]');
        $this->assertEquals($query, $entity->scopeWhereCriteria($query, ['foo']));
    }
}
