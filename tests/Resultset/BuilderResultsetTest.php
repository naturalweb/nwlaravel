<?php

namespace Tests\Resultset;

use Mockery as m;
use PHPUnit_Framework_TestCase;
use NwLaravel\Resultset\BuilderResultset;

class BuilderResultsetTest extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        parent::setUp();
        
        $sql = 'select * from foobar where foo = ?';
        $bindings = ['bar'];
        $this->statement = m::mock('PDOStatement');
        $this->statement->shouldReceive('execute')->once()->with($bindings)->andReturn(true);
        $pdo = m::mock('PDO');
        $pdo->shouldReceive('prepare')->once()->with($sql)->andReturn($this->statement);
        $conn = m::mock('Illuminate\Database\Connection');
        $conn->shouldReceive('getPdo')->once()->andReturn($pdo);
        $conn->shouldReceive('prepareBindings')->once()->with($bindings)->andReturn($bindings);
        $query = m::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('toSql')->once()->andReturn($sql);
        $query->shouldReceive('getBindings')->once()->andReturn($bindings);
        $query->shouldReceive('getConnection')->once()->andReturn($conn);
        
        $this->prototype = m::mock('Illuminate\Database\Eloquent\Model[]');

        $model = m::mock('Illuminate\Database\Query\Builder');
        $model->shouldReceive('newFromBuilder')->once()->andReturn($this->prototype);
        $this->builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $this->builder->shouldReceive('toBase')->once()->andReturn($query);
        $this->builder->shouldReceive('getModel')->once()->andReturn($model);
    }

    public function testConstruct()
    {
        $resultset = new BuilderResultset($this->builder);

        $this->assertInstanceOf('NwLaravel\Resultset\AbstractResultset', $resultset);
        $this->assertAttributeSame($this->prototype, 'prototype', $resultset);
        $this->assertAttributeSame($this->builder, 'builder', $resultset);
    }

    public function testMethodCurrentShouldReturnNull()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $resultset = new BuilderResultset($this->builder);

        $this->assertFalse($resultset->current());
    }

    public function testMethodCurrentShouldReturnNewInstance()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $resultset = new BuilderResultset($this->builder);

        $data = array('foobar' => 'baz');
        $this->statement->shouldReceive('fetch')->once()->with(\PDO::FETCH_ASSOC)->andReturn($data);

        $resultset->rewind();
        $current = $resultset->current();
        $this->assertEquals($current->toArray(), $data);
        $this->assertAttributeSame($data, 'currentData', $resultset);
    }
}
