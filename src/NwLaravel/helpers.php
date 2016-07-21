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
            $format = config('nwlaravel.date_format');

            $date = date_parse_from_format($format, $value);
            if ($format &&
                isset($date['error_count']) &&
                $date['error_count'] == 0 &&
                checkdate($date['month'], $date['day'], $date['year'])
            ) {
                return Carbon::createFromFormat($format, $value)->startOfDay();

            } else {
                $format = DB::getQueryGrammar()->getDateFormat();
                $date = date_parse_from_format($format, $value);
                if (isset($date['error_count']) && $date['error_count'] == 0) {
                    return Carbon::createFromFormat($format, $value);
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
        $format = DB::getQueryGrammar()->getDateFormat();

        if (is_numeric($value)) {
            $value = Carbon::createFromTimestamp($value);

        } elseif (is_string($value) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            $value = Carbon::createFromFormat('Y-m-d', $value)->startOfDay();

        } elseif (is_string($value)) {
            $dateFormat = config('nwlaravel.date_format');
            $date = date_parse_from_format($dateFormat, $value);

            if (isset($date['error_count']) && $date['error_count'] == 0) {
                if (checkdate($date['month'], $date['day'], $date['year'])) {
                    $value = Carbon::createFromFormat($dateFormat, $value)->startOfDay();
                }

            } else {
                $date = date_parse_from_format($format, $value);
                if (isset($date['error_count']) && $date['error_count'] == 0) {
                    $value = Carbon::createFromFormat($format, $value);
                }
            }

            if (strtotime($value) !== false) {
                $value = new Carbon($value);
            }
        }

        if ($value instanceof DateTime) {
            return $value->format($format);
        }

        return null;
    }
}

if (! function_exists('toFixed')) {
    /**
     * To Fixed
     *
     * @param int   $number  Integer Number
     * @param float $decimal Float Decimal
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
     * Date Formatter
     *
     * @param int $number Integer Number
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
     * @param int $storage Integer Storage
     *
     * @return string
     */
    function storageFormat($storage, $nivel = null)
    {
        switch (strtoupper($nivel)) {
            default:
                $multi = 1;
                break;
            case 'K':
                $multi = 1024*1000;
                break;
            case 'M':
                $multi = 1024*1000*1000;
                break;
            case 'G':
                $multi = 1024*1000*1000*1000;
                break;
            case 'T':
                $multi = 1024*1000*1000*1000*1000;
                break;
            case 'P':
                $multi = 1024*1000*1000*1000*1000*1000;
                break;
        }

        $storage = $storage * $multi;

        if ($multi > 1 && $storage >= $multi) {
            $storage = $storage / 1024;
        }

        $sufix = 'B';
        if ($storage >= 1000) {
            $storage = $storage / 1000;
            $sufix = 'KB';
        }

        if ($storage >= 1000) {
            $storage = $storage / 1000;
            $sufix = 'MB';
        }

        if ($storage >= 1000) {
            $storage = $storage / 1000;
            $sufix = 'GB';
        }

        if ($storage >= 1000) {
            $storage = $storage / 1000;
            $sufix = 'TB';
        }

        if ($storage >= 1000) {
            $storage = $storage / 1000;
            $sufix = 'PB';
        }

        return toFixed($storage, 1).$sufix;
    }
}

if (! function_exists('dateFormatter')) {
    /**
     * Date Formatter
     *
     * @param DateTime $date     Date Time
     * @param string   $dateType String Date Type
     * @param string   $timeType String Time Type
     *
     * @return string
     */
    function dateFormatter($date, $dateType, $timeType)
    {
        if ($date instanceof \DateTime) {
            $fmt = new \IntlDateFormatter(
                config('app.locale'),
                $dateType,
                $timeType,
                config('app.timezone')
            );

            return $fmt->format($date);
        }

        return $date;
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
        return dateFormatter($date, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::MEDIUM);
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
        return dateFormatter($date, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE);
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
            $date = sprintf(
                '%s - %s',
                dateFormatter($date, \IntlDateFormatter::LONG, \IntlDateFormatter::NONE),
                dateFormatter($date, \IntlDateFormatter::NONE, \IntlDateFormatter::MEDIUM)
            );
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
        return dateFormatter($date, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT);
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
     * @param int   $valor    Integer Valor
     * @param float $decimais Float Decimais
     *
     * @return string
     * @example : formatNumber(8712.335) = 8.712,34
     */
    function formatNumber($valor, $decimais = 2)
    {
        $valor   = floatval($valor);
        $decimais = intval($decimais);

        $pattern = sprintf('#,##0.%s', str_pad('', $decimais, '0'));

        $fmt = new \NumberFormatter(config('app.locale'), \NumberFormatter::DECIMAL);
        $fmt->setPattern($pattern);
        return $fmt->format($valor);
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

if (! function_exists('fileSystemAsset')) {
    /**
     * File System Asset
     *
     * @param string $path String Path
     *
     * @return string
     */
    function fileSystemAsset($path)
    {
        $default = config('filesystems.default');

        switch ($default) {
            case 'ftp':
                $url = trim(config('filesystems.disks.ftp.url'), '/').'/'.$path;
                break;
            case 'sftp':
                $url = trim(config('filesystems.disks.sftp.url'), '/').'/'.$path;
                break;
            case 's3':
                $url = trim(config('filesystems.disks.s3.url'), '/').'/'.$path;
                break;
            case 'local':
            default:
                $url = asset('storage/'.$path);
        }

        return $url;
    }
}
