<?php

namespace Tests\Serializers;

use Tests\TestCase;
use Mockery as m;
use NwLaravel\Serializers\DefaultSerializer;

class DefaultSerializerTest extends TestCase
{
    public function testImplementsInstanceOf()
    {
        $serializer = new DefaultSerializer;

        $this->assertInstanceOf('League\Fractal\Serializer\DataArraySerializer', $serializer);
    }

    public function testMethodsBasics()
    {
        $serializer = new DefaultSerializer;

        $this->assertEquals(['data' => ['item1', 'item2']], $serializer->collection('key', ['item1', 'item2']));
        $this->assertEquals(['col1', 'col2'], $serializer->item('key', ['col1', 'col2']));
        $this->assertEquals([], $serializer->meta([]));
        $this->assertEquals(['meta' => ['bar', 'baz']], $serializer->meta(['bar', 'baz']));

        $return = $serializer->includedData(m::mock('League\Fractal\Resource\ResourceInterface'), ['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $return);
    }


    public function testMethodPaginator()
    {
        $serializer = new DefaultSerializer;

        $paginator = m::mock('League\Fractal\Pagination\PaginatorInterface');
        $paginator->shouldReceive('getCurrentPage')->once()->withNoArgs()->andReturn('2');
        $paginator->shouldReceive('getLastPage')->once()->withNoArgs()->andReturn('4');
        $paginator->shouldReceive('getCount')->once()->withNoArgs()->andReturn('5');
        $paginator->shouldReceive('getTotal')->once()->withNoArgs()->andReturn('20');
        $paginator->shouldReceive('getPerPage')->once()->withNoArgs()->andReturn('5');

        $expected = [
            'pagination' => [
                'total' => 20,
                'count' => 5,
                'per_page' => 5,
                'current_page' => 2,
                'total_pages' => 4,
            ]
        ];
        $this->assertEquals($expected, $serializer->paginator($paginator));
    }
}
