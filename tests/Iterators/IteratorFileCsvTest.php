<?php
namespace Tests\Iterators;

use Tests\TestCase;
use NwLaravel\Iterators\IteratorFileCsv;
use NwLaravel\Iterators\AbstractIteratorFile;
use NwLaravel\Iterators\IteratorInterface;

class IteratorFileCsvTest extends TestCase
{
    public function testConstruct()
    {
        $headers = ['ID', 'NOME', 'FIELD1'];
        $iterator = new IteratorFileCsv(__DIR__.'/stub.csv', $headers, ',', "'", '\\');

        $this->assertInstanceOf(AbstractIteratorFile::class, $iterator);
        $this->assertInstanceOf(IteratorInterface::class, $iterator);

        $this->assertAttributeEquals(',', 'delimiter', $iterator);
        $this->assertAttributeEquals("'", 'enclosure', $iterator);
        $this->assertAttributeEquals('\\', 'escape', $iterator);
        $this->assertAttributeEquals([], 'defaults', $iterator);
        $this->assertAttributeEquals([], 'replace', $iterator);
        $this->assertAttributeEquals($headers, 'headers', $iterator);
    }

    public function testCount()
    {
        $iterator = new IteratorFileCsv(__DIR__.'/stub.csv');

        $this->assertEquals(4, count($iterator));
    }

    public function testGetHeaders()
    {
        $iterator = new IteratorFileCsv(__DIR__.'/stub.csv');

        $this->assertEquals(['id', 'name', 'foo_bar'], $iterator->getHeaders());

        $headers = ['ID', 'NOME', 'FIELD1'];
        $this->assertEquals($iterator, $iterator->setHeaders($headers));
        $this->assertEquals($headers, $iterator->getHeaders());
    }

    public function testLoopIterators()
    {
        $defaults = ['name' => 'nameDefault'];
        $replace = ['foo_bar' => 'barReplace'];

        $iterator = new IteratorFileCsv(__DIR__.'/stub.csv');
        $this->assertEquals($iterator, $iterator->setDefaults($defaults));
        $this->assertEquals($iterator, $iterator->setReplace($replace));

        $iterator->rewind();
        $this->assertEquals(['id'=>1,'name'=>'renato','foo_bar'=>'barReplace'], $iterator->current());

        $iterator->next();
        $this->assertEquals(['id'=>2,'name'=>'miguel','foo_bar'=>'barReplace'], $iterator->current());

        $iterator->next();
        $this->assertEquals([], $iterator->current());

        $iterator->next();
        $this->assertEquals(['id'=>3,'name'=>'Canção','foo_bar'=>'barReplace'], $iterator->current());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }
}
