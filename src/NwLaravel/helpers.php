<?php

/**
 * Arquivo de funções
 *
 * @license MIT
 * @package NwLaravel
 */

use \Carbon\Carbon;
use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Support\Facades\DB;

if (! function_exists('arrayFilterClean')) {

    function arrayFilterClean(array $input)
    {
        return array_filter($input, function ($value) {
            return (!empty($value) || $value == "0");
        });
    }
}

if (! function_exists('asCurrency')) {
    /**
     * Return a timestamp as DateTime object.
     *
     * @param mixed $value Mixed Value
     *
     * @return Carbon
     */
    function asCurrency($value)
    {
        // Numeric Pt
        $matchPt = (bool) preg_match('/^[0-9]{1,3}(\.[0-9]{3})*(\,[0-9]+)?$/', $value);
        $matchNumericPt = (bool) preg_match('/^[0-9]+(\,[0-9]+)$/', $value);
        if ($matchPt || $matchNumericPt) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        $matchEn = (bool) preg_match('/^[0-9]{1,3}(,[0-9]{3})*(\.[0-9]+)?$/', $value);
        if ($matchEn) {
            $value = str_replace(',', '', $value);
        }

        $matchNumeric = (bool) preg_match('/^[0-9]+(\.[0-9]+)?$/', $value);
        if ($matchNumeric) {
            return doubleval($value);
        }

        return null;
    }
}

if (! function_exists('asDateTime')) {
    /**
     * Return a timestamp as DateTime object.
     *
     * @param mixed $value Mixed Value
     *
     * @return Carbon
     */
    function asDateTime($value)
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof DateTime) {
            return Carbon::instance($value);
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp($value);
        }

        if (is_string($value) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        if (is_string($value)) {
            $formatDB = 'Y-m-d H:i:s';
            $dateFormat = config('nwlaravel.date_format');
            $formats = array_merge([$formatDB, $dateFormat], explode(" ", $dateFormat));
            foreach ($formats as $format) {
                $date = date_parse_from_format($format, $value);
                if ($date['error_count'] == 0 && $date['warning_count'] == 0) {
                    $value = Carbon::createFromFormat($format, $value);
                    if ($date['hour'] === false) {
                        $value->startOfDay();
                    }
                    return $value;
                }
            }

            if (strtotime($value) !== false) {
                return (new Carbon($value));
            }
        }

        return null;
    }
}

if (! function_exists('fromDateTime')) {
    /**
     * Convert a DateTime to a storable string.
     *
     * @param mixed $value Mixed Value
     *
     * @return string
     */
    function fromDateTime($value)
    {
        $formatDB = 'Y-m-d H:i:s';

        if (is_numeric($value)) {
            $value = Carbon::createFromTimestamp($value);

        } elseif (is_string($value) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            $value = Carbon::createFromFormat('Y-m-d', $value)->startOfDay();

        } elseif (is_string($value)) {
            $dateFormat = config('nwlaravel.date_format');
            $formats = array_merge([$dateFormat], explode(" ", $dateFormat));
            $formats[] = $formatDB;
            foreach ($formats as $format) {
                $date = date_parse_from_format($format, $value);
                if ($date['error_count'] == 0 && $date['warning_count'] == 0) {
                    $value = Carbon::createFromFormat($format, $value);
                    if ($date['hour'] === false) {
                        $value->startOfDay();
                    }
                    break;
                }
            }

            if (strtotime($value) !== false) {
                $value = new Carbon($value);
            }
        }

        if ($value instanceof DateTime) {
            return $value->format($formatDB);
        }

        return null;
    }
}

if (! function_exists('toFixed')) {
    /**
     * To Fixed
     *
     * @param int $number  Integer Number
     * @param int $decimal Float Decimal
     *
     * @return float
     */
    function toFixed($number, $decimal = 0)
    {
        $number = strval($number);
        $pos = strpos($number.'', ".");

        if ($pos > 0) {
            $int_str = substr($number, 0, $pos);
            $dec_str = substr($number, $pos+1);
            if (strlen($dec_str)>$decimal) {
                return floatval($int_str.($decimal>0?'.':'').substr($dec_str, 0, $decimal));
            } else {
                return floatval($number);
            }
        } else {
            return floatval($number);
        }
    }
}

if (! function_exists('numberHumans')) {
    /**
     * Number Humans
     *
     * @param float $number Number
     *
     * @return string
     */
    function numberHumans($number)
    {
        $sufix = '';
        if ($number >= 1000) {
            $number = $number / 1000;
            $sufix = 'K';
        }

        if ($number >= 1000) {
            $number = $number / 1000;
            $sufix = 'M';
        }

        if ($number >= 1000) {
            $number = $number / 1000;
            $sufix = 'B';
        }

        if ($number >= 1000) {
            $number = $number / 1000;
            $sufix = 'T';
        }

        return toFixed($number, 1).$sufix;
    }
}

if (! function_exists('storageFormat')) {
    /**
     * Storage Format
     *
     * @param float  $storage Integer Storage
     * @param string $nivel   Nivel Storage
     *
     * @return string
     */
    function storageFormat($storage, $nivel = null)
    {
        $storage = trim($storage);
        if (!is_numeric($storage)) {
            return $storage;
        }

        $sizes = ['KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4, 'PB' => 5];

        if (!is_null($nivel) && array_key_exists(strtoupper($nivel), $sizes)) {
            if ($storage < 1000) {
                return toFixed($storage, 1) . strtoupper($nivel);
            }

            for ($i = 0; $i < $sizes[strtoupper($nivel)]; $i++) {
                $storage = $storage * 1024;
            }
        }

        $sufix = 'B';
        foreach (array_keys($sizes) as $size) {
            if ($storage >= 1000) {
                $storage = $storage / 1000;
                $sufix = $size;
            }
        }

        return toFixed($storage, 1) . $sufix;
    }
}

if (! function_exists('dateFormatter')) {
    /**
     * Date Formatter
     *
     * @param DateTime $date     Date Time
     * @param string   $dateType String Date Type
     * @param string   $timeType String Time Type
     * @param string   $pattern  String Pattern
     *
     * @return string
     */
    function dateFormatter($date, $dateType, $timeType, $pattern = "")
    {
        if ($date instanceof \DateTime) {
            $fmt = new \IntlDateFormatter(
                config('app.locale'),
                $dateType,
                \IntlDateFormatter::NONE,
                config('app.timezone'),
                \IntlDateFormatter::GREGORIAN,
                $pattern
            );

            if (empty($pattern) && $dateType == \IntlDateFormatter::SHORT) {
                $fmt->setPattern(preg_replace("/y+/", "yyyy", $fmt->getPattern()));
            }

            $strTime = '';
            switch ($timeType) {
                case \IntlDateFormatter::SHORT:
                    $strTime = $date->format('H:i');
                    break;
                case \IntlDateFormatter::MEDIUM:
                    $strTime = $date->format('H:i:s');
                    break;
            }

            $newDate = clone $date;
            $newDate->setTime(0, 0);
            $strDate = ($dateType != \IntlDateFormatter::NONE) ? $fmt->format($newDate) : '';

            return trim(sprintf('%s %s', $strDate, $strTime));
        }

        return $date;
    }
}

if (! function_exists('formatPattern')) {
    /**
     * Format Pattern
     *
     * @param DateTime $date     Date Time
     * @param string   $pattern  String Pattern
     *
     * @return string
     */
    function formatPattern($date, $pattern)
    {
        if ($date instanceof \DateTime) {
            $fmt = new \IntlDateFormatter(
                config('app.locale'),
                IntlDateFormatter::NONE,
                IntlDateFormatter::NONE,
                config('app.timezone'),
                \IntlDateFormatter::GREGORIAN,
                $pattern
            );

            return $fmt->format($date);
        }

        return "";
    }
}

if (! function_exists('nameWeek')) {
    /**
     * Return Name Week
     *
     * @param DateTime $date Date Time
     *
     * @return string
     * @example : Domingo
     */
    function nameWeek($date)
    {
        return formatPattern($date, "EEEE");
    }
}

if (! function_exists('formatDateTime')) {
    /**
     * Format Date Time
     *
     * @param DateTime $date Date Time
     *
     * @return string
     * @example : DD/MM/YYYY HH:MIN:SS
     */
    function formatDateTime($date)
    {
        return dateFormatter($date, \IntlDateFormatter::SHORT, \IntlDateFormatter::MEDIUM);
    }
}

if (! function_exists('formatTimeShort')) {
    /**
     * Format Time Short
     *
     * @param DateTime $date Date Time
     *
     * @return string
     * @example : HH:MIN
     */
    function formatTimeShort($date)
    {
        return dateFormatter($date, \IntlDateFormatter::NONE, \IntlDateFormatter::SHORT);
    }
}

if (! function_exists('formatDate')) {
    /**
     * Format Date
     *
     * @param DateTime $date Date Time
     *
     * @return string
     * @example : DD/MM/YYYY
     */
    function formatDate($date)
    {
        return dateFormatter($date, \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE);
    }
}

if (! function_exists('formatTime')) {
    /**
     * Format Time
     *
     * @param DateTime $date Date Time
     *
     * @return string
     * @example : HH:MIN:SS
     */
    function formatTime($date)
    {
        return dateFormatter($date, \IntlDateFormatter::NONE, \IntlDateFormatter::MEDIUM);
    }
}

if (! function_exists('formatDateLong')) {
    /**
     * Format Date Long
     *
     * @param DateTime $date Date Time
     *
     * @return string
     * @example : [DIA] de [MES] de [ANO]
     */
    function formatDateLong($date)
    {
        return dateFormatter($date, \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);
    }
}

if (! function_exists('formatDateTimeLong')) {
    /**
     * Format Date Time Long
     *
     * @param DateTime $date Date Time
     *
     * @return string
     * @example : [DIA] de [MES] de [ANO] - HH:MIN:SS
     */
    function formatDateTimeLong($date)
    {
        if ($date instanceof \DateTime) {
            $date = sprintf('%s - %s', formatDateLong($date), formatTime($date));
        }

        return $date;
    }
}

if (! function_exists('formatDateFull')) {
    /**
     * Format Date Full
     *
     * @param DateTime $date Date Time
     *
     * @return string
     * @example : [SEMANA], [DIA] de [MES] de [ANO]
     */
    function formatDateFull($date)
    {
        return dateFormatter($date, \IntlDateFormatter::FULL, \IntlDateFormatter::NONE);
    }
}

if (! function_exists('formatDateTimeFull')) {
    /**
     * Format Date Time Full
     *
     * @param DateTime $date Date Time
     *
     * @return string
     * @example : [SEMANA], [DIA] de [MES] de [ANO] - HH:MIN:SS
     */
    function formatDateTimeFull($date)
    {
        if ($date instanceof \DateTime) {
            return sprintf(
                '%s - %s',
                dateFormatter($date, \IntlDateFormatter::FULL, \IntlDateFormatter::NONE),
                dateFormatter($date, \IntlDateFormatter::NONE, \IntlDateFormatter::MEDIUM)
            );
        }

        return $date;
    }
}

if (! function_exists('formatDateTimeShort')) {
    /**
     * Format Date Time Short
     *
     * @param DateTime $date Date Time
     *
     * @return string
     * @example : DD/MM/YYYY HH:MIN
     */
    function formatDateTimeShort($date)
    {
        return dateFormatter($date, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT);
    }
}

if (! function_exists('currencySymbol')) {
    /**
     * Return Currency Symbol
     *
     * @return string
     */
    function currencySymbol()
    {
        $fmt = new \NumberFormatter(config('app.locale'), \NumberFormatter::CURRENCY);
        return $fmt->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
    }
}

if (! function_exists('diffForHumans')) {
    /**
     * Diff date for Humans
     *
     * @param string|DataTime $date     String\DateTime Date
     * @param string|DataTime $other    String\DateTime Other
     * @param boolean         $absolute Boolean Absolute
     *
     * @return string
     * @example : 7 minutos atras
     */
    function diffForHumans($date, $other = null, $absolute = false)
    {
        if ($date instanceof \DateTime) {
            $date = Carbon::instance($date);
            if (!$other instanceof Carbon) {
                $other = null;
            }
            return $date->diffForHumans($other, $absolute);
        }

        return '';
    }
}

if (! function_exists('now')) {
    /**
     * Now Date Time
     *
     * @return Carbon
     */
    function now()
    {
        return Carbon::now();
    }
}

if (! function_exists('formatCurrency')) {
    /**
     * Formato moeda conforme locale
     *
     * @param float $valor Float Valor
     *
     * @return string
     * @example : formatCurrency(8712.335) = R$8.712,34
     */
    function formatCurrency($valor)
    {
        $fmt = new \NumberFormatter(config('app.locale'), \NumberFormatter::CURRENCY);
        return $fmt->format(floatval($valor));
    }
}

if (! function_exists('formatNumber')) {
    /**
     * Formato numero conforme locale
     *
     * @param float $valor
     * @param int   $decimal
     *
     * @return string
     * @example : formatNumber(8712.335) = 8.712,34
     */
    function formatNumber($valor, $decimal = 2)
    {
        $valor   = floatval($valor);
        $decimal = intval($decimal);

        $pattern = sprintf('#,##0.%s', str_pad('', $decimal, '0'));

        $fmt = new \NumberFormatter(config('app.locale'), \NumberFormatter::DECIMAL);
        $fmt->setPattern($pattern);
        return $fmt->format($valor);
    }
}

if (! function_exists('maskCep')) {
    /**
     * Cria a mascara do cep
     *
     * @param string $value
     *
     * @return string
     * @example : maskCep(12345678) = 12345-678
     */
    function maskCep($value)
    {
        $capture = '/^([0-9]{5})([0-9]{3})$/';
        $format = '$1-$2';
        $value = preg_replace('[^0-9]', '', $value);
        $result = preg_replace($capture, $format, $value);
        if (!is_null($result)) {
            $value = $result;
        }
        return $value;
    }
}

if (! function_exists('maskCpf')) {
    /**
     * Cria a mascara do cpf
     *
     * @param string $value
     *
     * @return string
     * @example : maskCpf(12345678901) = 123.456.789-01
     */
    function maskCpf($value)
    {
        $capture = '/^([0-9]{3})([0-9]{3})([0-9]{3})([0-9]{2})$/';
        $format = '$1.$2.$3-$4';
        $value = preg_replace('[^0-9]', '', $value);
        $result = preg_replace($capture, $format, $value);
        if (!is_null($result)) {
            $value = $result;
        }
        return $value;
    }
}

if (! function_exists('maskCnpj')) {
    /**
     * Cria a mascara do cnpj
     *
     * @param string $value
     *
     * @return string
     * @example : maskCpf(00123456/0001-78) = 00.123.456/0001-78
     */
    function maskCnpj($value)
    {
        $capture = '/^([0-9]{2,3})([0-9]{3})([0-9]{3})([0-9]{4})([0-9]{2})$/';
        $format = '$1.$2.$3/$4-$5';
        $value = preg_replace('[^0-9]', '', $value);
        $result = preg_replace($capture, $format, $value);
        if (!is_null($result)) {
            $value = $result;
        }
        return $value;
    }
}

if (! function_exists('maskCpfOrCnpj')) {
    /**
     * Cria a mascara do cpf ou cnpj
     *
     * @param string $value
     *
     * @return string
     */
    function maskCpfOrCnpj($value)
    {
        $value = preg_replace('[^0-9]', '', $value);
        if (strlen($value)==11) {
            return maskCpf($value);
        } else {
            return maskCnpj($value);
        }
    }
}

if (! function_exists('numberRoman')) {
    /**
     * Number integer to Roman
     *
     * @param integer $integer
     *
     * @return string
     */
    function numberRoman($integer)
    {
        $table = array(
            'M'  =>1000,
            'CM' =>900,
            'D'  =>500,
            'CD' =>400,
            'C'  =>100,
            'XC' =>90,
            'L'  =>50,
            'XL' =>40,
            'X'  =>10,
            'IX' =>9,
            'V'  =>5,
            'IV' =>4,
            'I'  =>1
        );

        if ($integer < 1 || $integer > 3999) {
            return $integer;
        }

        $return = '';
        while ($integer > 0) {
            foreach ($table as $rom => $arb) {
                if ($integer >= $arb) {
                    $integer -= $arb;
                    $return .= $rom;
                    break;
                }
            }
        }

        return $return;
    }
}

if (! function_exists('numberLetra')) {
    /**
     * Number to Letra
     *
     * @param integer    $number
     * @param array|null $letras
     * @param integer    $nivel
     *
     * @return string
     */
    function numberLetra($number, array $letras = null, $nivel = -1)
    {
        $number = trim($number);
        if (preg_match('/[^0-9]/', $number)) {
            return $number;
        }

        $num = intval($number);

        $letrasOrig = array(
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
            'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
        );

        if (is_null($letras)) {
            $letras = $letrasOrig;
        }

        $nivel++;
        if ($num > count($letras) && array_key_exists($nivel, $letras)) {
            $letraParent = $letras[$nivel];
            foreach ($letrasOrig as $value) {
                $letras[] = $letraParent.$value;
            }

            return numberLetra($num, $letras, $nivel);
        }

        $index = $num-1;
        if (array_key_exists($index, $letras)) {
            $return = $letras[$index];
        } else {
            $return = $number;
        }

        return $return;
    }
}

if (! function_exists('activePattern')) {
    /**
     * Return class $active, para url pattern,
     * caso contrario $inactive
     *
     * @param string $path     String Path
     * @param string $active   String Active
     * @param string $inactive String Inactive
     *
     * @return string
     */
    function activePattern($path, $active = 'active', $inactive = '')
    {
        $method = '\Illuminate\Support\Facades\Request::is';
        return call_user_func_array($method, (array) $path) ? $active : $inactive;
    }
}

if (! function_exists('activeRoute')) {
    /**
     * Return class $active, para route currentRoute
     * caso contrario $inactive
     *
     * @param string $route    String Route
     * @param string $active   String Active
     * @param string $inactive String Inactive
     *
     * @return string
     */
    function activeRoute($route, $active = 'active', $inactive = '')
    {
        $method = '\Illuminate\Support\Facades\Route::currentRouteName';
        return call_user_func_array($method, [])==$route ? $active : $inactive;
    }
}

if (! function_exists('linkRoute')) {
    /**
     * Cria o link com currentRoute
     *
     * @param string $route String Route
     * @param string $label String Label
     * @param string $class String Class
     *
     * @return string
     */
    function linkRoute($route, $label, $class = '')
    {
        return sprintf('<a href="%s" class="%s">%s</a>', route($route), $class?:activeRoute($route), $label);
    }
}

if (! function_exists('activity')) {
    /**
     * Log activity
     *
     * @param string    $action
     * @param string    $description
     * @param \Eloquent $model
     *
     * @return bool
     */
    function activity($action, $description, $model = null)
    {
        return app('nwlaravel.activity')->log($action, $description, $model);
    }
}
