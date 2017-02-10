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

    public function testValidateCurrentPasswordWithGuardAndField()
    {
        $user = new \stdClass;
        $user->pass = '$2y$07$BCryptRequires22Chrcte/VlQH0piJtjXl.0t1XkA8pw9dMXTpOq';

        $auth = m::mock('Illuminate\Contracts\Auth\Factory');
        $auth->shouldReceive('guard')->twice()->with('users')->andReturn($auth);
        $auth->shouldReceive('user')->twice()->andReturn($user);

        $this->app->instance('Illuminate\Contracts\Auth\Factory', $auth);

        $this->assertTrue($this->resolver->validateCurrentPassword('foobar', 'rasmuslerdorf', ['users', 'pass']));
        $this->assertFalse($this->resolver->validateCurrentPassword('foobar', '0rasmuslerdorf', ['users', 'pass']));
    }

    public function testValidateCurrentPasswordWithoutGuard()
    {
        $user = new \stdClass;
        $user->password = '$2y$07$BCryptRequires22Chrcte/VlQH0piJtjXl.0t1XkA8pw9dMXTpOq';

        $auth = m::mock('Illuminate\Contracts\Auth\Factory');
        $auth->shouldReceive('guard')->never();
        $auth->shouldReceive('user')->twice()->andReturn($user);

        $this->app->instance('Illuminate\Contracts\Auth\Factory', $auth);

        $this->assertTrue($this->resolver->validateCurrentPassword('foobar', 'rasmuslerdorf'));
        $this->assertFalse($this->resolver->validateCurrentPassword('foobar', '0rasmuslerdorf'));
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

    /**
     * @dataProvider providerCurrencyPtBR
     */
    public function testCurrencyPtBr($value)
    {
        $this->assertTrue($this->resolver->validateCurrency('foobar', $value));
    }

    public function providerCurrencyPtBR()
    {
        return [
            ['1,0'],
            ['12,34'],
            ['432,00'],
            ['1.067,1'],
            ['71.111,00'],
            ['871.341,00'],
            ['1.871.341,01'],
            ['31.871.341,01'],
            ['461.654.999,232'],
            ['8.761.254.109,232'],
            ['871341,0054'],
        ];
    }

    /**
     * @dataProvider providerCurrencyEn
     */
    public function testCurrencyEn($value)
    {
        $this->assertTrue($this->resolver->validateCurrency('foobar', $value));
    }

    public function providerCurrencyEn()
    {
        return [
            ['0'],
            ['1'],
            ['143'],
            ['985445'],
            ['871341.030'],
            ['1.0'],
            ['12.34'],
            ['432.00'],
            ['1,067.1'],
            ['71,111.00'],
            ['871,341.0430'],
            ['1,871,341.02221'],
            ['31,871,341.01'],
            ['461,654,999.22'],
            ['8,761,254,109.22'],
        ];
    }

    /**
     * @dataProvider providerCurrencyInvalid
     */
    public function testCurrencyInvalid($value)
    {
        $this->assertFalse($this->resolver->validateCurrency('foobar', $value));
    }

    public function providerCurrencyInvalid()
    {
        return [
            ['.0'],
            [',40'],
            ['143.'],
            ['1e0'],
            ['12#34'],
            ['1.067.1'],
            ['71,111,00'],
            ['1,871.341.01'],
            ['31.871,341.01'],
            ['461,654,999,22'],
            ['8,761.254,109.22'],
            ['88,71.24,1.22'],
            ['2,3143.2'],
            ['33,31,43.00'],
        ];
    }

    public function testValidateRequiredIfAll()
    {
        $data = ['pessoa' => 'J', 'tipo' => '1'];
        $this->resolver->setData($data);

        $this->assertTrue($this->resolver->validateRequiredIfAll('foo', 'bar', ['pessoa', 'J', 'tipo', '1']));
        $this->assertFalse($this->resolver->validateRequiredIfAll('foo', null, ['pessoa', 'J', 'tipo', '1']));

        $this->assertTrue($this->resolver->validateRequiredIfAll('foo', null, ['pessoa', 'J', 'tipo', '0']));
        $this->assertTrue($this->resolver->validateRequiredIfAll('foo', null, ['pessoa', 'F', 'tipo', '1']));
        $this->assertTrue($this->resolver->validateRequiredIfAll('foo', null, ['pessoa', 'F', 'tipo', '0']));
        $this->assertTrue($this->resolver->validateRequiredIfAll('foo', null, ['pessoa', 'F']));
        $this->assertTrue($this->resolver->validateRequiredIfAll('foo', null, ['tipo', '0']));
    }

    public function testValidateRequiredIfUnlessAll()
    {
        $data = ['pessoa' => 'J', 'tipo' => '1'];
        $this->resolver->setData($data);

        $this->assertTrue($this->resolver->validateRequiredUnlessAll('foo', 'bar', ['pessoa', 'F', 'tipo', '0']));
        $this->assertFalse($this->resolver->validateRequiredUnlessAll('foo', null, ['pessoa', 'F', 'tipo', '0']));

        $this->assertTrue($this->resolver->validateRequiredUnlessAll('foo', null, ['pessoa', 'F', 'tipo', '1']));
        $this->assertTrue($this->resolver->validateRequiredUnlessAll('foo', null, ['pessoa', 'J', 'tipo', '0']));
        $this->assertTrue($this->resolver->validateRequiredUnlessAll('foo', null, ['pessoa', 'J', 'tipo', '1']));
        $this->assertTrue($this->resolver->validateRequiredUnlessAll('foo', null, ['pessoa', 'J']));
        $this->assertTrue($this->resolver->validateRequiredUnlessAll('foo', null, ['tipo', '1']));
    }

    /**
     * @dataProvider providerCondicoesRequired
     */
    public function testRequiredIfAllCondicoes($condicao)
    {
        $data = ['pessoa' => 'J', 'tipo' => '1'];
        $this->resolver->setData($data);
        $this->assertTrue($this->resolver->validateRequiredIfAll('foo', null, ['pessoa', 'J', 'tipo', '0']));
        $this->assertAttributeEquals(['foo' => ['nullable']], 'rules', $this->resolver);
    }

    /**
     * @dataProvider providerCondicoesRequired
     */
    public function testRequiredUnlessAllCondicoes($condicao)
    {
        $data = ['pessoa' => 'J', 'tipo' => '1'];
        $this->resolver->setData($data);
        $this->assertTrue($this->resolver->validateRequiredUnlessAll('foo', null, ['pessoa', 'J', 'tipo', '0']));
        $this->assertAttributeEquals(['foo' => ['nullable']], 'rules', $this->resolver);
    }

    public function providerCondicoesRequired()
    {
        return [
            [['pessoa', 'F', 'tipo', '1']],
            [['pessoa', 'J', 'tipo', '0']],
            [['pessoa', 'J', 'tipo', '1']],
            [['pessoa', 'J']],
            [['tipo', '1']],
        ];
    }
}
