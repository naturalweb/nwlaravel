<?php
namespace Tests\Transformer;

use Tests\TestCase;
use Mockery as m;
use NwLaravel\Transformer\AbstractTransformer;

class AbstractTransformerTest extends TestCase
{
    public function testConstructInstanceOf()
    {
        $transformer = m::mock(AbstractTransformer::class.'[]');
        $this->assertAttributeEquals(false, 'includeData', $transformer);
        $this->assertFalse($transformer->hasIncludeData());
    }

    public function testMethods()
    {
        $transformer = m::mock(AbstractTransformer::class.'[]', [true]);

        $this->assertAttributeEquals(true, 'includeData', $transformer);
        $this->assertTrue($transformer->hasIncludeData());
        $this->assertNull($transformer->formatDate('', 'd/m/Y'));
        $this->assertEquals('04/12/1982', $transformer->formatDate(new \Datetime('1982-12-04'), 'd/m/Y'));
    }
}
