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

        $this->assertInstanceOf('Prettus\Validator\AbstractValidator', $base);
        $this->assertAttributeEquals($factory, 'validator', $base);
        $this->assertEquals($factory, $base->getValidator());
        $this->assertAttributeEquals('id_foo', 'keyName', $base);
        $this->assertEquals([], $base->getRules('bar'));
        
        $messages = ['email.email' => 'Email Errado'];
        $this->assertAttributeEquals($messages, 'messages', $base);
        $this->assertEquals($messages, $base->getMessages());

        $attributes = ['email' => 'Email Address'];
        $this->assertAttributeEquals($attributes, 'attributes', $base);
        $this->assertEquals($attributes, $base->getAttributes());

        $base->setId(33);
        $expected = [
            'email' => ['required', 'email', 'unique:users,email,33,id_foo'],
            'foo_id' => ['exists:tablename,id,id,420'],
        ];
        $this->assertEquals($expected, $base->getRules('create'));
    }

    public function testPassesShouldReceiveTrue()
    {
        $factory = m::mock('Illuminate\Validation\Factory');
        $validator = m::mock('Illuminate\Validation\Validator');
        $validator->shouldReceive('fails')->once()->andReturn(false);
        $validator->shouldReceive('messages')->never();

        $data = ['email' => 'foo@bar.com', 'name' => 'Renato'];
        $messages = ['email.email' => 'Email Errado'];
        $attributes = ['email' => 'Email Address'];
        $rules = [
            'email' => ['required', 'email', 'unique:users,email,33,id_foo'],
            'foo_id' => ['exists:tablename,id,id,420'],
            'name' => ['required'],
        ];
        $factory->shouldReceive('make')
            ->once()
            // ->with($data, $rules, $messages, $attributes)
            ->andReturn($validator);

        $base = new FooValidator($factory);
        $base->with($data);

        $this->assertTrue($base->passes('create'));
        $this->assertAttributeEquals([], 'errors', $base);
    }

    public function testPassesShouldReceiveFails()
    {
        $factory = m::mock('Illuminate\Validation\Factory');
        $validator = m::mock('Illuminate\Validation\Validator');
        $validator->shouldReceive('fails')->once()->andReturn(true);
        $validator->shouldReceive('messages')->once()->andReturn(['ErrorMessages']);

        $data = ['email' => 'foo@bar.com', 'name' => 'Renato'];
        $messages = ['email.email' => 'Email Errado'];
        $attributes = ['email' => 'Email Address'];
        $rules = [
            'name' => ['required'], ['exists:[other]'],
        ];
        $factory->shouldReceive('make')
            ->once()
            // ->with($data, $rules, $messages, $attributes)
            ->andReturn($validator);

        $base = new FooValidator($factory);
        $base->with($data);

        $this->assertFalse($base->passes('update'));
        $this->assertAttributeEquals(['ErrorMessages'], 'errors', $base);

    }
}

class FooValidator extends BaseValidator
{
    protected $rules = [
        'create' => [
            'email' => 'required|email|unique:users',
            'foo_id' => 'exists:tablename,id,id,[id]',
        ],
        'update' => ['name' => 'required|exists:[other]'],
        'delete' => ['id' => 'not_exists:foo'],
    ];

    protected $messages = [
        'email.email' => 'Email Errado'
    ];

    protected $attributes = [
        'email' => 'Email Address',
    ];
}
