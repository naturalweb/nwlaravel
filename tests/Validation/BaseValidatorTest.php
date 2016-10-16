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
        $base->with(['id' => '420']);
        $base->setKeyName('id_foo');

        $this->assertInstanceOf('Prettus\Validator\LaravelValidator', $base);
        $this->assertEquals($factory, $base->getValidator());
        $this->assertAttributeEquals('id_foo', 'keyName', $base);
        $this->assertEquals([], $base->getRules('bar'));

        $base->setId(33);
        $expected = [
            'email' => ['required', 'email', 'unique:users,email,33,id_foo'],
            'foo_id' => ['exists:tablename,id,id,420'],
        ];
        $this->assertEquals($expected, $base->getRules('create'));
    }
}

class FooValidator extends BaseValidator
{
    protected $rules = [
        'create' => [
            'email' => 'required|email|unique:users',
            'foo_id' => 'exists:tablename,id,id,[id]',
        ],
        'update' => ['name' => 'required|unique:foo'],
        'delete' => ['id' => 'not_exists:foo'],
    ];
}
