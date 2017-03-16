<?php

namespace Tests\OAuth;

use Tests\TestCase;
use NwLaravel\Locale\Locale;

class LocaleTest extends TestCase
{
    /**
     * @dataProvider providerOrdinals
     */
    public function testExtensoOrdinal($expected, $number)
    {
        $this->assertEquals($expected, Locale::extensoOrdinal($number));
    }

    public function providerOrdinals()
    {
        return [
            ['décimo', ' 10.00 '],
            ['10,00', '10,00'],
            ['-', 0],
            ['primeiro', 1.33],
            ['vigésimo primeiro', 21],
            ['1000º', 1000],
            ['nongentésimo nonagésimo nono', 999],
            ['103580º', 103580.10],
            ['centésimo décimo sétimo', 117],
            ['centésimo', 100],
            ['quarto', 4],
        ];
    }

    /**
     * @dataProvider providerCardinals
     */
    public function testExtensoCardinal($expected, $number)
    {
        $this->assertEquals($expected, Locale::extensoCardinal($number));
    }

    public function providerCardinals()
    {
        return [
            ['dez', ' 10.00 '],
            ['10,00', '10,00'],
            ['zero', 0],
            ['treze', 13],
            ['vinte e um', 21],
            ['cento e dois', 102],
            ['um mil e trinta e nove', 1039],
            ['cento e tres mil e quinhentos e oitenta', 103580],
            ['cento e dezessete', 117],
            ['cem', 100],
            ['quatro', 4],
            ['oito mil', 8000],
            ['oitenta mil', 80000],
            ['oitocentos mil', 800000],
            ['oito milhões', 8000000],
            ['cem, onze', 100.11],
            ['onze mil e trezentos e sessenta e quatro, oitenta e nove', 11364.89],
            ['um milhão, quarenta e quatro', 1000000.44],
            ['um milhão e duzentos mil, quarenta e quatro', 1200000.44],
            ['duzentos e cinquenta e dois mil e um', 252001,00],
        ];
    }

    /**
     * @dataProvider providerCurrencies
     */
    public function testExtensoCurrency($expected, $number)
    {
        $this->assertEquals($expected, Locale::extensoCurrency($number));
    }

    public function providerCurrencies()
    {
        return [
            ['um real', 1],
            ['dez reais', ' 10.00 '],
            ['10,00', '10,00'],
            ['zero reais', 0],
            ['setenta e sete centavos', 0.77],
            ['um centavo', 0.01],
            ['vinte e um reais', 21.00],
            ['cem reais', 100.00],
            ['cem reais e doze centavos', 100.12],
            ['um mil e trinta e nove reais e quarenta e tres centavos', 1039.43],
            ['cento e tres mil e quinhentos e oitenta reais e um centavo', 103580.01],
            ['cento e dezessete reais e sessenta e sete centavos', 117.67],
            ['cem reais e dezessete centavos', 100.17],
            ['quatro reais e noventa e nove centavos', 4.99],
            ['duzentos e cinquenta e dois mil e um reais e dois centavos', 252001.02],
            ['um milhão de reais e quarenta e quatro centavos', 1000000.44],
            ['cem milhões de reais', 100000000],
            ['um milhão e duzentos mil reais e quarenta e quatro centavos', 1200000.44],
            ['oito mil reais', 8000],
            ['oitenta mil reais', 80000],
            ['oitocentos mil reais', 800000],
            ['oito milhões de reais', 8000000],
        ];
    }
}
