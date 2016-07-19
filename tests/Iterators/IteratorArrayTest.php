<?php
namespace Tests\Iterators;

use Tests\TestCase;
use NwLaravel\Iterators\IteratorArray;
use NwLaravel\Iterators\IteratorInterface;

class IteratorArrayTest extends TestCase
{
    public function testConstruct()
    {
        $iterator = new IteratorArray;

        $this->assertInstanceOf(\ArrayIterator::class, $iterator);
        $this->assertInstanceOf(IteratorInterface::class, $iterator);
        $this->assertAttributeEquals([], 'defaults', $iterator);
        $this->assertAttributeEquals([], 'replace', $iterator);
    }

    public function testCurrentShouldReturnWithDefaultAndReplace()
    {
        $currents = [['id' => 1, 'name' => 'Renato', 'bar' => 'barCurrent']];
        $defaults = ['name' => 'nameDefault', 'foo' => 'value1', 'bar' => 'barDefault'];
        $replace = ['bar' => 'barReplace', 'field' => 'other'];

        $iterator = new IteratorArray($currents);
        $this->assertEquals($iterator, $iterator->setDefaults($defaults));
        $this->assertEquals($iterator, $iterator->setReplace($replace));

        $expected = [
            'name' => 'Renato',
            'foo' => 'value1',
            'bar' => 'barReplace',
            'id' => 1,
            'field' => 'other'
        ];
        $this->assertEquals($expected, $iterator->current());
    }
}
