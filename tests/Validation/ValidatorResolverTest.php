<?php
namespace Tests\Foundation\Validator;

use Tests\TestCase;
use Mockery as m;
use NwLaravel\Validation\ValidatorResolver;
use Illuminate\Validation\Validator;
use Symfony\Component\Translation\TranslatorInterface;
use Respect\Validation\Rules;

class ValidatorResolverTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->translator = m::mock(TranslatorInterface::class);
        $this->resolver = new ValidatorResolver($this->translator, [], []);
    }

    public function testConstructInstanceOf()
    {
        $this->assertInstanceOf(Validator::class, $this->resolver);
        $this->assertEquals($this->translator, $this->resolver->getTranslator());
    }

    public function testValidatePattern()
    {
        $this->assertTrue($this->resolver->validatePattern('foobar', '/[a-z]+/', []));
        $this->assertTrue($this->resolver->validatePattern('foobar', '/[0-z]+/', []));
    }

    public function testValidateCurrentPassword()
    {
        $hash = '$2y$07$BCryptRequires22Chrcte/VlQH0piJtjXl.0t1XkA8pw9dMXTpOq';

        $this->assertTrue($this->resolver->validateCurrentPassword('foobar', 'rasmuslerdorf', [$hash]));
        $this->assertFalse($this->resolver->validateCurrentPassword('foobar', '0rasmuslerdorf', [$hash]));
    }

    public function testValidateDocument()
    {
        $this->assertTrue($this->resolver->validateDocument('foobar', '907.280.974-27', []));
        $this->assertFalse($this->resolver->validateDocument('foobar', '123.456.789-00', []));

        $this->assertTrue($this->resolver->validateDocument('foobar', '14.358.649/0001-93', []));
        $this->assertFalse($this->resolver->validateDocument('foobar', '99.945.678/0001-90', []));
    }

    public function testValidateCpf()
    {
        $this->assertTrue($this->resolver->validateCpf('foobar', '907280974-27', []));
        $this->assertFalse($this->resolver->validateCpf('foobar', '907280974-2', []));
        $this->assertFalse($this->resolver->validateCpf('foobar', '00000000000', []));
        $this->assertFalse($this->resolver->validateCpf('foobar', '11111111111', []));
    }

    public function testValidateCnpj()
    {
        $this->assertTrue($this->resolver->validateCnpj('foobar', '14358649/0001-93', []));
        $this->assertFalse($this->resolver->validateCnpj('foobar', '14358649/0001-9', []));
        $this->assertFalse($this->resolver->validateCnpj('foobar', '11111111111111', []));
        $this->assertFalse($this->resolver->validateCnpj('foobar', '00112233/0111-59', []));
    }

    public function testValidateNotExistsShouldReceiveFalse()
    {
        $mock = m::mock('Illuminate\Validation\PresenceVerifierInterface');
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->with('users', 'foobar', '1', null, null, [])->andReturn(true);
        $this->resolver->setPresenceVerifier($mock);

        $this->assertFalse($this->resolver->validateNotExists('foobar', '1', ['users']));
    }

    public function testValidateNotExistsShouldReceiveTrue()
    {
        $mock = m::mock('Illuminate\Validation\PresenceVerifierInterface');
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->with('users', 'foobar', '1', null, null, [])->andReturn(false);
        $this->resolver->setPresenceVerifier($mock);

        $this->assertTrue($this->resolver->validateNotExists('foobar', '1', ['users']));
    }

    public function testCallThrowBadMethodCallException()
    {
        $this->setExpectedException("BadMethodCallException", "Method [validateXptoInvalid] does not exist.");
        $this->resolver->validateXptoInvalid();
    }

    /**
     * @dataProvider providerRespectRulesValid
     */
    public function testRespectValidations($rule, $value, $args)
    {
        $method = 'validate'.ucfirst($rule);
        $this->assertTrue($this->resolver->{$method}('foobar', $value, (array) $args));
    }

    public function providerRespectRulesValid()
    {
        //  [rule],          [value],        [args]
            
        return [
            ['Alnum',       'abc 123 [',    ['[']],
            ['Alpha',       'abc }',        ['}']],
            ['Age',         '1998-06-21',   ['18', '50']],
            ['PostalCode',  '02179-000',    ['BR']],
            ['Roman',       'XLIX',         []],

        ];
    }
}
