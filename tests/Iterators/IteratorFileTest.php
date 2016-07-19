<?php
namespace Tests\Iterators;

use Tests\TestCase;
use NwLaravel\Iterators\IteratorFile;

class IteratorFileTest extends TestCase
{
    public function testConstruct()
    {
        $headers = [];
        $iterator = new IteratorFile(__DIR__.'/stub.csv');

        $this->assertInstanceOf(\Iterator::class, $iterator);
        $this->assertInstanceOf(\Countable::class, $iterator);
    }

    public function testFileNoExistsThrowException()
    {
        $this->setExpectedException(\RuntimeException::class, 'Couldn\'t open file "foo.txt"');

        $iterator = new IteratorFile('foo.txt');
    }

    public function testCount()
    {
        $iterator = new IteratorFile(__DIR__.'/stub.csv');

        $this->assertEquals(5, count($iterator));
    }

    public function testLoopIterators()
    {
        $defaults = ['name' => 'nameDefault'];
        $replace = ['foo_bar' => 'barReplace'];

        $iterator = new IteratorFile(__DIR__.'/stub.csv');

        $iterator->rewind();
        $this->assertAttributeEquals('Id;Name;Foo Bar', 'lineCurrent', $iterator);
        $this->assertEquals('Id;Name;Foo Bar', $iterator->current());
        $this->assertEquals('0', $iterator->key());

        $iterator->next();
        $this->assertAttributeEquals('1;renato;test1', 'lineCurrent', $iterator);
        $this->assertEquals('1;renato;test1', $iterator->current());
        $this->assertEquals('1', $iterator->key());

        $iterator->next();
        $this->assertAttributeEquals('2;miguel;test2', 'lineCurrent', $iterator);
        $this->assertEquals('2;miguel;test2', $iterator->current());
        $this->assertEquals('2', $iterator->key());

        $iterator->next();
        $this->assertAttributeEquals(';;', 'lineCurrent', $iterator);
        $this->assertEquals(';;', $iterator->current());
        $this->assertEquals('3', $iterator->key());

        $iterator->next();
        $this->assertAttributeEquals('3;Canção', 'lineCurrent', $iterator);
        $this->assertEquals('3;Canção', $iterator->current());
        $this->assertEquals('4', $iterator->key());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }
}
