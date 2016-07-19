<?php

namespace Tests\Repositories\Resultset;

use Mockery as m;
use PHPUnit_Framework_TestCase;
use NwLaravel\Repositories\Resultset\AbstractResultset;

class StubAbstractResultset extends AbstractResultset
{
    public function __construct($statement)
    {
        $this->statement = $statement;
    }
}

class AbstractResultsetTest extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        parent::setUp();
        $this->statement = $this->getMock('PDOStatement');
    }

    public function testConstruct()
    {
        $resultSet = new StubAbstractResultset($this->statement);

        $this->assertInstanceOf('NwLaravel\Repositories\Resultset\AbstractResultset', $resultSet);
        $this->assertAttributeSame($this->statement, 'statement', $resultSet);
        $this->assertAttributeSame(false, 'currentData', $resultSet);
        $this->assertAttributeSame(false, 'rewindComplete', $resultSet);
        $this->assertAttributeSame(false, 'initialized', $resultSet);
        $this->assertAttributeSame(-1, 'position', $resultSet);
    }

    public function testMethodGetStatement()
    {
        $resultSet = new StubAbstractResultset($this->statement);

        $this->assertSame($this->statement, $resultSet->getStatement());
    }

    public function testMethodGetStatementThrowException()
    {
        $this->setExpectedException('RuntimeException');

        $resultSet = new StubAbstractResultset('');
        $resultSet->getStatement();
    }

    public function testMethodInitialized()
    {
        $resultSet = new StubAbstractResultset($this->statement);
        $resultSet->initialize();

        $this->assertAttributeSame(true, 'initialized', $resultSet);
    }

    public function testMethodRewidNextMethodKeyAndMethodValid()
    {
        $resultSet = new StubAbstractResultset($this->statement);

        $this->assertFalse($resultSet->valid());

        $data = array('foo' => 'bar');
        $this->statement
             ->expects($this->exactly(2))
             ->method('fetch')
             ->with(\PDO::FETCH_ASSOC)
             ->will($this->returnValue($data));

        $resultSet->rewind();
        $this->assertAttributeEquals(true, 'rewindComplete', $resultSet);
        $this->assertAttributeSame($data, 'currentData', $resultSet);
        $this->assertAttributeEquals(0, 'position', $resultSet);
        $this->assertEquals(0, $resultSet->key());
        $this->assertTrue($resultSet->valid());

        $current = $resultSet->next();
        $this->assertEquals($data, $current);
        $this->assertAttributeEquals(1, 'position', $resultSet);
        $this->assertEquals(1, $resultSet->key());
        $this->assertTrue($resultSet->valid());

        $self = $resultSet->current();
        $this->assertSame($current, $self);
    }

    public function testRewidTwoExecuteThrowException()
    {
        $this->setExpectedException('RuntimeException');

        $resultSet = new StubAbstractResultset($this->statement);

        $data = array('foo' => 'bar');
        $this->statement
             ->expects($this->once())
             ->method('fetch')
             ->with(\PDO::FETCH_ASSOC)
             ->will($this->returnValue($data));

        $resultSet->rewind();
        $resultSet->rewind();
    }

    public function testMethodCount()
    {
        $resultSet = new StubAbstractResultset($this->statement);
        
        $num = 30;
        $this->statement
             ->expects($this->once())
             ->method('rowCount')
             ->with()
             ->will($this->returnValue($num));

        $this->assertEquals($num, $resultSet->count());
        $this->assertAttributeEquals($num, 'rowCount', $resultSet);
        $this->assertEquals($num, $resultSet->count());
    }
    
    public function testGetFields()
    {
        $resultSet = new StubAbstractResultset($this->statement);

        $data = array('foo' => 'vr1', 'bar' => 'vr2');
        $this->statement
             ->expects($this->once())
             ->method('fetch')
             ->with(\PDO::FETCH_ASSOC)
             ->will($this->returnValue($data));

        $this->assertEquals(['foo', 'bar'], $resultSet->getFields());
        $this->assertEquals(2, $resultSet->getFieldCount());
    }

    public function testGetFieldsWithObject()
    {
        $resultSet = new StubAbstractResultset($this->statement);

        $values = array('foo' => 'vr1', 'bar' => 'vr2');

        $data = $this->getMockBuilder('Illuminate\Database\Eloquent\Model')
            ->setMethods(array('toArray'))
            ->getMock();
        $data->expects($this->once())
             ->method('toArray')
             ->will($this->returnValue($values));

        $this->statement
             ->expects($this->once())
             ->method('fetch')
             ->with(\PDO::FETCH_ASSOC)
             ->will($this->returnValue($data));

        $resultSet->rewind();
        $this->assertEquals(['foo', 'bar'], $resultSet->getFields());
        $this->assertEquals(2, $resultSet->getFieldCount());
    }
}
