<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography;

final class Normalizer
{
    private const FOLD = [
        'À' => 'a',
        'Á' => 'a',
        'Â' => 'a',
        'Ã' => 'a',
        'Ä' => 'a',
        'Å' => 'a',
        'à' => 'a',
        'á' => 'a',
        'â' => 'a',
        'ã' => 'a',
        'ä' => 'a',
        'å' => 'a',
        'Æ' => 'ae',
        'æ' => 'ae',
        'Ç' => 'c',
        'ç' => 'c',
        'È' => 'e',
        'É' => 'e',
        'Ê' => 'e',
        'Ë' => 'e',
        'è' => 'e',
        'é' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'Ì' => 'i',
        'Í' => 'i',
        'Î' => 'i',
        'Ï' => 'i',
        'ì' => 'i',
        'í' => 'i',
        'î' => 'i',
        'ï' => 'i',
        'Ñ' => 'n',
        'ñ' => 'n',
        'Ò' => 'o',
        'Ó' => 'o',
        'Ô' => 'o',
        'Õ' => 'o',
        'Ö' => 'o',
        'ò' => 'o',
        'ó' => 'o',
        'ô' => 'o',
        'õ' => 'o',
        'ö' => 'o',
        'Œ' => 'oe',
        'œ' => 'oe',
        'Ù' => 'u',
        'Ú' => 'u',
        'Û' => 'u',
        'Ü' => 'u',
        'ù' => 'u',
        'ú' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'Ý' => 'y',
        'Ÿ' => 'y',
        'ý' => 'y',
        'ÿ' => 'y',
        'Š' => 's',
        'š' => 's',
        'Ž' => 'z',
        'ž' => 'z',
        'ß' => 'ss',
    ];

    public static function key(string $value): string
    {
        $value = strtr(trim($value), self::FOLD);
        $value = strtolower($value);

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }
}
