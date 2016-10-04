<?php
namespace Tests\Transformer;

use Tests\TestCase;
use Mockery as m;
use NwLaravel\Transformer\AbstractTransformer;

class AbstractTransformerTest extends TestCase
{
    public function testFormatDate()
    {
        $transformer = m::mock(AbstractTransformer::class.'[]');

        $this->assertNull($transformer->formatDate('', 'd/m/Y'));
        $this->assertEquals('04/12/1982', $transformer->formatDate(new \Datetime('1982-12-04'), 'd/m/Y'));
    }
}
