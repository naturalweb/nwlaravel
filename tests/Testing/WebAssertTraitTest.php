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
        $stub->response->original = m::mock('Illuminate\Contracts\View\View');
        $stub->response->original->shouldReceive('name')->twice()->andReturn('foo-show');

        $stub->assertView('foo-show');

        try {
            $stub->assertView('bar-show', 'More Message');
        } catch (\Exception $e) {
            $this->assertInstanceOf('PHPUnit_Framework_ExpectationFailedException', $e);
            
            $expectedMessage = "More Message";
            $expectedMessage .= PHP_EOL."Failed asserting that 'bar-show' The response view actual is 'foo-show'.";
            $this->assertEquals($expectedMessage, $e->getMessage());
        }
    }

    public function testAssertTraitExists()
    {
        $stub = new TestStub;

        $stub->assertTraitExists('NwLaravel\Testing\WebAssertTrait', $stub);

        try {
            $stub->assertTraitExists('TraitNoExist', $stub);
        } catch (\Exception $e) {
            $this->assertInstanceOf('PHPUnit_Framework_ExpectationFailedException', $e);
            
            $expectedMessage = "Failed asserting not exists Trait instance of interface 'TraitNoExist'.";
            $expectedMessage .= PHP_EOL."Failed asserting that an array has the key 'TraitNoExist'.";
            $this->assertEquals($expectedMessage, $e->getMessage());
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


