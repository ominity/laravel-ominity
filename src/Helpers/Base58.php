<?php

namespace Ominity\Laravel\Helpers;

use InvalidArgumentException;

class Base58
{
    private static $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    public static function encode($data)
    {
        $baseCount = strlen(self::$alphabet);
        $encoded = '';
        $num = gmp_init(bin2hex($data), 16);

        while (gmp_cmp($num, 0) > 0) {
            [$num, $rem] = gmp_div_qr($num, $baseCount);
            $encoded = self::$alphabet[gmp_intval($rem)].$encoded;
        }

        foreach (str_split($data) as $byte) {
            if ($byte === "\x00") {
                $encoded = self::$alphabet[0].$encoded;
            } else {
                break;
            }
        }

        return $encoded;
    }

    public static function decode($data)
    {
        $baseCount = strlen(self::$alphabet);
        $num = gmp_init(0);

        // Decode the Base58 string to a number
        foreach (str_split($data) as $char) {
            $pos = strpos(self::$alphabet, $char);
            if ($pos === false) {
                throw new InvalidArgumentException('Invalid character in Base58 string');
            }
            $num = gmp_add(gmp_mul($num, $baseCount), $pos);
        }

        $decoded = hex2bin(gmp_strval($num, 16));

        // Add leading zero bytes
        foreach (str_split($data) as $char) {
            if ($char === self::$alphabet[0]) {
                $decoded = "\x00".$decoded;
            } else {
                break;
            }
        }

        return $decoded;
    }
}
