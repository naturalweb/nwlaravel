<?php

namespace Tests\Resultset;

use Mockery as m;
use PHPUnit_Framework_TestCase;
use NwLaravel\Resultset\ModelResultset;

class ModelResultsetTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $conn = m::mock('Illuminate\Database\Connection');

        $this->statement = $this->createMock('PDOStatement');
        
        $this->prototype = m::mock('Illuminate\Database\Eloquent\Model[]');

        $this->model = m::mock('Illuminate\Database\Eloquent\Model[newFromBuilder]');
        $this->model
             ->shouldReceive('newFromBuilder')
             ->once()
             ->andReturn($this->prototype);
    }
    
    public function testConstructAndInitialize()
    {
        $resultset = new ModelResultset($this->statement, $this->model);

        $this->assertInstanceOf('NwLaravel\Resultset\AbstractResultset', $resultset);
        $this->assertAttributeSame($this->prototype, 'prototype', $resultset);
        $this->assertAttributeSame($this->statement, 'statement', $resultset);
        $this->assertAttributeSame($this->model, 'model', $resultset);
    }

    public function testMethodCurrentShouldReturnNull()
    {
        $resultset = new ModelResultset($this->statement, $this->model);

        $this->assertFalse($resultset->current());
    }

    public function testMethodCurrentShouldReturnNewInstance()
    {
        $resultset = new ModelResultset($this->statement, $this->model);

        $data = array('foobar' => 'baz');

        $this->statement
             ->expects($this->once())
             ->method('fetch')
             ->with(\PDO::FETCH_ASSOC)
             ->will($this->returnValue($data));

        $resultset->rewind();
        $current = $resultset->current();
        $this->assertEquals($current->toArray(), $data);
        $this->assertAttributeSame($data, 'currentData', $resultset);
    }
}
