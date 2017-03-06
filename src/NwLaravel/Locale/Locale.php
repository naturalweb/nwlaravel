<?php

namespace NwLaravel\Locale;

class Locale
{
    /**
     * Extenso Ordinal
     *
     * @param float $number
     *
     * @return string
     * @static
     */
    public static function extensoOrdinal($number)
    {
        return self::numberToWords($number, false, true);
    }

    /**
     * Extenso Currency
     *
     * @param float $number
     *
     * @return string
     * @static
     */
    public static function extensoCurrency($number)
    {
        return self::numberToWords($number, true, false);
    }

    /**
     * Extenso Cardinal
     *
     * @param float $number
     *
     * @return string
     * @static
     */
    public static function extensoCardinal($number)
    {
        return self::numberToWords($number, false, false);
    }

    /**
     * Extenso
     *
     * @param float $number
     *
     * @return string
     * @static
     */
    public static function extenso($number)
    {
        return self::extensoCurrency($number);
    }

    /**
     * Translate
     *
     * @param boolean $currency
     * @param boolean $ordinals
     *
     * @return string
     * @static
     */
    protected static function translate($currency = false, $ordinals = false)
    {
        $translate = array();

        if ($ordinals) {
            $translate['singular'] = array("", "", "milésimo", "milhão", "bilhão", "trilhão", "quatrilhão");
            $translate['plural'] = array("", "", "milésimo", "milhões", "bilhões", "trilhões", "quatrilhões");
            $translate['centanas'] = array("", "centésimo", "ducentésimo", "trecentésimo", "quadrigentésimo", "quingentésimo", "sexcentésimo", "septigentésimo", "octigentésimo", "nongentésimo");
            $translate['dezenas'] = array("", "décimo", "vigésimo", "trigésimo", "quadragésimo", "quinquagésimo", "sexagésimo", "septuagésimo", "octogésimo", "nonagésimo");
            $translate['dezenas10'] = array("décimo", "décimo primeiro", "décimo segundo", "décimo terceiro", "décimo quarto", "décimo quinto", "décimo sexto", "décimo sétimo", "décimo oitavo", "décimo nono");
            $translate['unidades'] = array("", "primeiro", "segundo", "terceiro", "quarto", "quinto", "sexto", "sétimo", "oitavo", "nono");
            $translate['cento'] = "centésimo";
            $translate['separator'] = " ";
            $translate['preposicao'] = " ";
            $translate['zero'] = "-";
        } else {
            $translate['singular'] = array("", "", "mil", "milhão", "bilhão", "trilhão", "quatrilhão");
            $translate['plural'] = array("", "", "mil", "milhões", "bilhões", "trilhões", "quatrilhões");
            $translate['centanas'] = array("", "cem", "duzentos", "trezentos", "quatrocentos", "quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos");
            $translate['dezenas'] = array("", "dez", "vinte", "trinta", "quarenta", "cinquenta", "sessenta", "setenta", "oitenta", "noventa");
            $translate['dezenas10'] = array("dez", "onze", "doze", "treze", "quatorze", "quinze", "dezesseis", "dezessete", "dezoito", "dezenove");
            $translate['unidades'] = array("", "um", "dois", "tres", "quatro", "cinco", "seis", "sete", "oito", "nove");
            $translate['cento'] = "cento";
            $translate['separator'] = " e ";
            $translate['preposicao'] = "";
            $translate['zero'] = "zero";
            $translate['separator_decimal'] = ", ";

            if($currency){
                $translate['singular'][0] = "centavo";
                $translate['singular'][1] = "real";
                $translate['plural'][0]   = "centavos";
                $translate['plural'][1]   = "reais";
                $translate['zero'] = "zero reais";
                $translate['preposicao'] = " de "; // ex: um milhao de reais
                $translate['separator_decimal'] = " e ";
            }
        }

        return $translate;
    }

    /**
     * Number to Extenso String
     *
     * @param float $number
     *
     * @return string
     */
    protected static function numberToWords($number, $currency = false, $ordinals = false)
    {
        $number = trim($number);
        if (preg_match("/[^0-9\.]/", $number)) {
            return $number;
        }

        $number = $ordinals ? intval($number) : floatval($number);

        if ($ordinals && $number >= 1000) {
            return $number.'º';
        }

        $translate = self::translate($currency, $ordinals);

        $number = number_format($number, 2, ".", ".");
        $inteiro = explode(".", $number);
        for ($i=0;$i<count($inteiro);$i++) {
            for ($ii=strlen($inteiro[$i]);$ii<3;$ii++) {
                $inteiro[$i] = "0".$inteiro[$i];
            }
        }

        $fim = count($inteiro) - ($inteiro[count($inteiro)-1] > 0 ? 1 : 2);
        $rt = '';
        $z = 0;
        for ($i=0;$i<count($inteiro);$i++) {
            $number = $inteiro[$i];
            if (($number > 100) && ($number < 200)) {
                $rc = $translate['cento'];
            } else {
                $rc = $translate['centanas'][$number[0]];
            }

            if ($number[1] < 2) {
                $rd = "";
            } else {
                $rd = $translate['dezenas'][$number[1]];
            }

            if ($number > 0) {
                if ($number[1] == 1) {
                    $ru = $translate['dezenas10'][$number[2]];
                } else {
                    $ru = $translate['unidades'][$number[2]];
                }
            } else {
                $ru = "";
            }

            $r = $rc;
            if ($rc && ($rd || $ru)) {
                $r .= $translate['separator'];
            }

            $r .= $rd;
            if ($rd && $ru) {
                $r .= $translate['separator'];
            }

            $r .= $ru;

            $t = count($inteiro)-1-$i;

            if ($r) {
                $r .= " ";
                if ($number > 1) {
                    $r .= $translate['plural'][$t];
                } else {
                    $r .= $translate['singular'][$t];
                }
            }
            
            if ($number == "000") {
                $z++; 
            } elseif ($z > 0) {
                $z--;
            }
            
            if (($t==1) && ($z>0) && ($inteiro[0] > 0)) {
                if ($z > 1) {
                    $r .= $translate['preposicao'];
                }

                $r .= $translate['plural'][$t];
            }

            if ($r) {
                $rt = $rt;
                if (($i > 0) && ($i <= $fim) && ($inteiro[0] > 0) && ($z < 1)) {

                    $rt .= (!$currency && $t == 0 && $i==$fim) ? ', ' : $translate['separator'];

                } else {
                    if ($t == 0 && $rt) {
                        $rt .= $currency ? $translate['separator'] : ', ';
                    } else {
                        $rt .= " ";
                    }
                }

                $rt .= $r;
            }
        }
        
        $rt = preg_replace("/\s+/", " ", $rt);
        $rt = preg_replace("/\s+,\s*/", ", ", $rt);

        return trim($rt) ?: $translate['zero'];
    }

    /**
     * Ufs
     *
     * @return array
     */
    public static function states()
    {
        return config('locales.ufs');
    }

    /**
     * Sigla Ufs
     *
     * @return array
     */
    public static function siglaStates()
    {
        $keys = array_keys(self::ufs());
        return array_combine($keys, $keys);
    }
}
