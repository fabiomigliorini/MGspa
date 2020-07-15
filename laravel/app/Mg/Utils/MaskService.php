<?php

namespace Mg\Utils;

class MaskService
{
    public static function mask($val, $mask)
    {
        $maskared = '';
        $k = 0;
        for ($i = 0; $i<=strlen($mask)-1; $i++) {
            if ($mask[$i] == '#') {
                if (isset($val[$k])) {
                    $maskared .= $val[$k++];
                }
            } else {
                if (isset($mask[$i])) {
                    $maskared .= $mask[$i];
                }
            }
        }
        return trim($maskared);
    }

    public static function ean8($val)
    {
        return static::mask($val, '#### ####');
    }

    public static function ean13($val)
    {
        return static::mask($val, '### #### ### ###');
    }

    public static function ean($val)
    {
        switch (strlen($val)) {
            case 8:
                return static::ean8($val);
                break;

            case 13:
                return static::ean13($val);
                break;

            default:
                return $val;
                break;
        }
    }
}
