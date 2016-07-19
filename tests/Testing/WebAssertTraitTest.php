<?php

namespace Tests\Testing;

use Tests\TestCase;
use Mockery as m;

class WebAssertTraitTest extends TestCase
{
    public function testAssertView()
    {
        $stub = new TestStub;

        $stub->response = new \stdClass;
        $stub->response->original = m::mock('Illuminate\Contracts\View');
        $stub->response->original->shouldReceive('name')->twice()->andReturn('foo-show');

        $stub->assertView('foo-show');

        try {
            $stub->assertView('bar-show');
        } catch (\Exception $e) {
            $this->assertInstanceOf('PHPUnit_Framework_ExpectationFailedException', $e);
            $this->assertEquals(
                "Failed asserting that 'bar-show' The response view actual is 'foo-show'.",
                $e->getMessage()
            );
        }
    }

    public function testAssertViewNoDefined()
    {
        $stub = new TestStub;

        $stub->response = new \stdClass;

        try {
            $stub->assertView('bar-show');
        } catch (\Exception $e) {
            $this->assertInstanceOf('PHPUnit_Framework_ExpectationFailedException', $e);
            $this->assertEquals(
                "Failed asserting that 'bar-show' The response view not defined.",
                $e->getMessage()
            );
        }
    }

    public function testCallProtectedMethod()
    {
        $stub = new TestStub;

        $this->assertEquals(['foo' => 'bar'], $stub->callProtectedMethod($stub, 'protegido', ['foo', 'bar']));
    }
}


