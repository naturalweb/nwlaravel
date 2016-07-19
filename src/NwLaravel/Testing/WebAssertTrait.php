<?php

namespace NwLaravel\Testing;

use Illuminate\Contracts\View;
use PHPUnit_Framework_TestCase as PHPUnit;

trait WebAssertTrait
{
    /**
     * Assert that the response view has name
     *
     * @param string $name
     * @param string $message
     */
    public function assertView($name, $message = '')
    {
        PHPUnit::assertThat($name, new ConstraintView($this->response), $message);
    }

    /**
     * Assert Trait Exists
     *
     * @param  string $expected
     * @param  Object $object
     * @param  string $message
     *
     * @return void
     * @throws
     */
    public function assertTraitExists($expected, $object, $message = '')
    {
        $traits = class_uses($object);
        $message = $message ?: sprintf("Failed asserting not exists Trait instance of interface '%s'.", $expected);
        PHPUnit::assertArrayHasKey($expected, $traits, $message);
    }

    /**
     * Execute method protected
     *
     * @param object $object Object
     * @param string $method Method a Execute
     * @param array  $args   Params
     */
    public function callProtectedMethod($object, $method, array $args = array())
    {
        $class = new \ReflectionClass(get_class($object));
        $method = $class->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }
}
