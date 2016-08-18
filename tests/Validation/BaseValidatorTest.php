<?php
namespace Tests\Foundation\Validator;

use Tests\TestCase;
use Mockery as m;
use NwLaravel\Validation\BaseValidator;

class BaseValidatorTest extends TestCase
{
    public function testConstructInstanceOf()
    {
        $factory = m::mock('Illuminate\Validation\Factory');
        $base = new FooValidator($factory);
        $base->setKeyName('id_foo');

        $this->assertInstanceOf('Prettus\Validator\LaravelValidator', $base);
        $this->assertEquals($factory, $base->getValidator());
        $this->assertAttributeEquals('id_foo', 'keyName', $base);
        $this->assertEquals([], $base->getRules('bar'));

        $base->setId(33);
        $this->assertEquals(['email' => ['required', 'email', 'unique:users,email,33,id_foo']], $base->getRules('create'));
    }
}

class FooValidator extends BaseValidator
{
    protected $rules = [
        'create' => ['email' => 'required|email|unique:users'],
        'update' => ['name' => 'required|unique:foo'],
        'delete' => ['id' => 'not_exists:foo'],
    ];
}
