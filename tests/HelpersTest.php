<?php

namespace Tests;

use Tests\TestCase;
use Mockery as m;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

class HelpersTest extends TestCase
{
    protected $config;

    public function setUp()
    {
        parent::setUp();

        $grammar = m::mock('Illuminate\Database\Grammar');
        $grammar->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
        DB::shouldReceive('getQueryGrammar')->andReturn($grammar);

        $this->config = m::mock('config');
        $this->config->shouldReceive('get')->with('nwlaravel.date_format', null)->andReturn('d/m/Y H:i');
        $this->config->shouldReceive('get')->with('app.timezone', null)->andReturn('America/Sao_Paulo');
        $this->app->instance('config', $this->config);
    }

    public function testArrayFilterClean()
    {
        $array = [
            0 => null,
            1 => '',
            'foo' => 'bar',
            2 => '0',
            'zero' => 0,
            false
        ];
        $this->assertEquals(['foo' => 'bar', 2 => '0', 'zero' => 0, 3 => false], arrayFilterClean($array));
    }

    public function testAsCurrency()
    {
        $this->assertNull(asCurrency(''));
        $this->assertNull(asCurrency('a'));
        $this->assertNull(asCurrency('12.21,33'));
        $this->assertNull(asCurrency('432.21,33'));

        $this->assertEquals('1221.53', asCurrency('1.221,53'));
        $this->assertEquals('1221.73', asCurrency('1,221.73'));
        $this->assertEquals('431221.03', asCurrency('431.221,03'));
        $this->assertEquals('2431221.03', asCurrency('2.431.221,03'));
        $this->assertEquals('77524.99', asCurrency('77524.99'));
        $this->assertEquals('54324.54', asCurrency('54324,54'));
    }

    public function testAsDateTime()
    {
        $now = new \DateTime;
        
        $this->assertEquals(null, asDateTime(''));
        $this->assertEquals(Carbon::instance($now), asDateTime($now));
        $this->assertEquals(Carbon::createFromTimestamp(1), asDateTime(1));
        $this->assertEquals(Carbon::createFromFormat('Y-m-d', '2010-10-24')->startOfDay(), asDateTime('2010-10-24'));
        $this->assertEquals(null, asDateTime(new \stdClass));
        $this->assertEquals(null, asDateTime('teste'));
        $expected = new Carbon('2016-04-30');
        $expected->startOfDay();
        $this->assertEquals($expected, asDateTime('30/04/2016'));
        $this->assertEquals(new Carbon('2016-04-30 23:10:44'), asDateTime('2016-04-30 23:10:44'));
        $this->assertEquals(new Carbon('10 September 2000'), asDateTime('10 September 2000'));
    }
     
    public function testFromDateTime()
    {
        $this->assertEquals('2015-12-01 16:19:21', fromDateTime(1448993961));
        $this->assertEquals('2015-12-01 00:00:00', fromDateTime('2015-12-01'));
        $this->assertEquals('2015-12-04 00:00:00', fromDateTime('04/12/2015'));
        $this->assertEquals('2015-10-30 22:11:00', fromDateTime('30/10/2015 22:11'));
        $this->assertEquals('2016-04-30 23:10:44', fromDateTime('2016-04-30 23:10:44'));
        $this->assertEquals(null, fromDateTime('teste'));
    }

    public function testNumberHumans()
    {
        $this->assertEquals('-1', numberHumans(-1));
        $this->assertEquals('999', numberHumans(999));
        $this->assertEquals('1K', numberHumans(1001));
        $this->assertEquals('1.1K', numberHumans(1100));
        $this->assertEquals('1.9K', numberHumans(1999));
        $this->assertEquals('999.9K', numberHumans(999999));
        $this->assertEquals('1M', numberHumans(1000000));
        $this->assertEquals('1.1M', numberHumans(1100000));
        $this->assertEquals('1.9M', numberHumans(1999999));
        $this->assertEquals('999.9M', numberHumans(999999999));
        $this->assertEquals('1B', numberHumans(1000000000));
        $this->assertEquals('1.1B', numberHumans(1100000000));
        $this->assertEquals('1.9B', numberHumans(1999999999));
        $this->assertEquals('999.9B', numberHumans(999999999999));
        $this->assertEquals('1T', numberHumans(1000000000000));
        $this->assertEquals('1.1T', numberHumans(1100000000000));
        $this->assertEquals('1.9T', numberHumans(1999999999999));
        $this->assertEquals('999.9T', numberHumans(999999999999990));
    }

    public function providerNumberHumans()
    {
        return [
            ['123.6KB',123.66, 'K'],
            ['44.7MB',44.79, 'M'],
            ['99GB', 99, 'G'],
            ['5TB', 5, 'T'],
            ['1PB', 1, 'P'],
        ];
    }

    /**
     * @dataProvider providerStorageFormatWithBytes
     */
    public function testStorageFormatWithBytes($expected, $bytes)
    {
        $this->assertEquals($expected, storageFormat($bytes));
    }

    /**
     * @dataProvider providerStorageFormatWithNivel
     */
    public function testStorageFormatWithNivel($expected, $size, $nivel)
    {
        $this->assertEquals($expected, storageFormat($size, $nivel));
    }

    public function providerStorageFormatWithNivel()
    {
        return [
            ['123.6KB',123.66, 'KB'],
            ['44.7MB',44.79, 'MB'],
            ['99GB', 99, 'GB'],
            ['5TB', 5, 'TB'],
            ['1PB', '1', 'PB'],
            ['1a2', '1a2', 'PB'],
        ];
    }

    public function providerStorageFormatWithBytes()
    {
        return [
            ['44.7B', ' 44.79 '],
            ['999B', 999],
            ['1KB', 1099],
            ['7.1KB', 7180],
            ['87KB', 87099],
            ['999.9KB', 999999],
            ['1MB', 1000000],
            ['7.1MB', 7100000],
            ['87.5MB', 87509999],
            ['999.9MB', 999999999],
            ['1GB', 1000000000],
            ['77.1GB', 77100000000],
            ['999.9GB', 999999999999],
            ['1TB', 1000000000000],
            ['1PB', 1000000000000000],
            ['aBc', ' aBc '],
        ];
    }

    public function testHelpersNumbersFormatters()
    {
        $this->config->shouldReceive('get')->with('app.locale', null)->andReturn('pt_BR');

        $this->assertEquals('1.234,500', formatNumber(1234.5, 3));
        $this->assertEquals('R$4.321,00', formatCurrency(4321));
    }

    public function testHelpersDateFormatters()
    {
        $this->config->shouldReceive('get')->with('app.locale', null)->andReturn('pt_BR');

        $date = new \DateTime('29-09-2015 13:20:10');

        $this->assertEquals('ERROR', formatDateTimeFull('ERROR'));
        $this->assertEquals('terça-feira, 29 de setembro de 2015 - 13:20:10', formatDateTimeFull($date));
        $this->assertEquals('terça-feira, 29 de setembro de 2015', formatDateFull($date));
        $this->assertEquals('29 de setembro de 2015 - 13:20:10', formatDateTimeLong($date));
        $this->assertEquals('29 de setembro de 2015', formatDateLong($date));
        $this->assertEquals('29/09/2015 13:20:10', formatDateTime($date));
        $this->assertEquals('29/09/2015 13:20', formatDateTimeShort($date));
        $this->assertEquals('29/09/2015', formatDate($date));
        $this->assertEquals('13:20:10', formatTime($date));
        $this->assertEquals('13:20', formatTimeShort($date));
    }

    public function testDateFormatter()
    {
        $this->config->shouldReceive('get')->with('app.locale', null)->andReturn('en-us');

        $date = new \DateTime('29-09-2015 13:20:10');
        $dateFormatter = dateFormatter($date, \IntlDateFormatter::FULL, \IntlDateFormatter::NONE);
        $this->assertEquals('Tuesday, September 29, 2015', $dateFormatter);
        $this->assertEquals('ERROR', dateFormatter('ERROR', \IntlDateFormatter::FULL, \IntlDateFormatter::NONE));
    }

    public function testCurrencySymbol()
    {
        $this->config->shouldReceive('get')->with('app.locale', null)->andReturn('en-us');

        $this->assertEquals('$', currencySymbol());
    }

    public function testDatesNow()
    {
        $now = now();
        $this->assertEquals(date('Y-m-d H:i'), $now->format('Y-m-d H:i'));
    }

    public function testDiffHumans()
    {
        $date = new Carbon('2015-03-28');
        $now = new Carbon('2015-09-29');

        $this->assertEquals('6 months before', diffForHumans($date, $now));
        $this->assertEquals('6 months after', diffForHumans($now, $date));
        $this->assertEquals('1 month ago', diffForHumans(now()->subMonth(1), true));
        $this->assertEquals('6 months', diffForHumans($date, $now, true));
        $this->assertEquals('2 weeks ago', diffForHumans(now()->subWeek(2)));
        $this->assertEquals('2 weeks from now', diffForHumans(now()->addWeek(2)));
        $this->assertEquals('1 year', diffForHumans(now()->addYear(1), null, true));
        
        $this->assertEmpty(diffForHumans('ERROR'));
    }

    public function testMaskCpf()
    {
        $this->assertEquals("123.456.099-00", maskCpf("12345609900"));
        $this->assertEquals("2345609900", maskCpf("2345609900"));
    }

    public function testMaskCep()
    {
        $this->assertEquals("12345-609", maskCep("12345609"));
        $this->assertEquals("234560999", maskCep("234560999"));
    }

    public function testMaskCnpj()
    {
        $this->assertEquals("33.123.456/0001-44", maskCnpj("33123456000144"));
        $this->assertEquals("033.123.456/0001-44", maskCnpj("033123456000144"));
        $this->assertEquals("331234560001", maskCnpj("331234560001"));
    }

    public function testMaskCpfOrCnpj()
    {
        $this->assertEquals("123.456.099-00", maskCpfOrCnpj("12345609900"));
        $this->assertEquals("99.123.456/0001-22", maskCpfOrCnpj("99123456000122"));
        $this->assertEquals("099.123.456/0001-22", maskCpfOrCnpj("099123456000122"));
        $this->assertEquals("0991234560001221", maskCpfOrCnpj("0991234560001221"));
        $this->assertEquals("2345609900", maskCpfOrCnpj("2345609900"));
    }

    public function testActivePattern()
    {
        \Illuminate\Support\Facades\Request::shouldReceive('is')->once()->andReturn(true);
        $this->assertEquals('ativo', activePattern('/nameRoute', 'ativo', 'inativo'));
    }

    public function testActiveRouteCurrent()
    {
        Route::shouldReceive('currentRouteName')->once()->andReturn('nameRoute');
        $this->assertEquals('ativo', activeRoute('nameRoute', 'ativo', 'inativo'));
    }

    public function testActiveRouteNotCurrent()
    {
        Route::shouldReceive('currentRouteName')->once()->andReturn('otherRoute');
        $this->assertEquals('inativo', activeRoute('nameRoute', 'ativo', 'inativo'));
    }

    public function testLinkRouteWithClass()
    {
        $generator = m::mock('UrlGenerator');
        $generator->shouldReceive('route')->once()->with('nameRoute', [], true)->andReturn('http:/localhost/route');
        $this->app->instance('url', $generator);

        Route::shouldReceive('currentRouteName')->never();
        $urlExpected = '<a href="http:/localhost/route" class="btn">CliqueAqui</a>';
        $this->assertEquals($urlExpected, linkRoute('nameRoute', 'CliqueAqui', 'btn'));
    }

    public function testLinkRouteCurrent()
    {
        $generator = m::mock('UrlGenerator');
        $generator->shouldReceive('route')->once()->with('nameRoute', [], true)->andReturn('http:/localhost/route');
        $this->app->instance('url', $generator);

        Route::shouldReceive('currentRouteName')->once()->andReturn('nameRoute');
        $urlExpected = '<a href="http:/localhost/route" class="active">CliqueAqui</a>';
        $this->assertEquals($urlExpected, linkRoute('nameRoute', 'CliqueAqui'));
    }

    public function testLinkRouteNotCurrent()
    {
        $generator = m::mock('UrlGenerator');
        $generator->shouldReceive('route')->once()->with('nameRoute', [], true)->andReturn('http:/localhost/route');
        $this->app->instance('url', $generator);

        Route::shouldReceive('currentRouteName')->once()->andReturn('otherRoute');
        $urlExpected = '<a href="http:/localhost/route" class="">CliqueAqui</a>';
        $this->assertEquals($urlExpected, linkRoute('nameRoute', 'CliqueAqui'));
    }

    public function testNumberRoman()
    {
        $this->assertEquals('0', numberRoman(0));
        $this->assertEquals('IX', numberRoman(9));
        $this->assertEquals('MMMCMXCIX', numberRoman(3999));
        $this->assertEquals('4000', numberRoman(4000));
    }

    public function testNumberLetra()
    {
        $this->assertEquals('1234abcedefghijklmnopqrstuvwxyz', numberLetra('1234abcedefghijklmnopqrstuvwxyz'));
        $this->assertEquals('alpha', numberLetra('alpha'));
        $this->assertEquals('0', numberLetra(0));
        $this->assertEquals('A', numberLetra(1));
        $this->assertEquals('B', numberLetra(2));
        $this->assertEquals('C', numberLetra(3));
        $this->assertEquals('Z', numberLetra(26));
        $this->assertEquals('AA', numberLetra(27));
        $this->assertEquals('AB', numberLetra(28));
        $this->assertEquals('DZ', numberLetra(130));
        $this->assertEquals('ZZ', numberLetra(702));
        $this->assertEquals('AAA', numberLetra(703));
        $this->assertEquals('AAB', numberLetra(704));
    }

    public function testActivityLog()
    {
        $model = new \StdClass;
        $model->id = 11;

        $activityManager = m::mock('NwLaravel\ActivityLog\ActivityManager');
        $activityManager->shouldReceive('log')->once()->with('created', 'desc foo bar', $model)->andReturn(true);
        $this->app->instance('nwlaravel.activity', $activityManager);

        $this->assertTrue(activity('created', 'desc foo bar', $model));
    }
}
