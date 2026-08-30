<?php

namespace App\Search\Linguistics;

/**
 * Транслитерация кириллица↔латиница и исправление неправильной раскладки
 * клавиатуры. Никакого ИИ — чистые таблицы соответствий.
 */
class Transliterator
{
    /** Кириллица → латиница (каноническая форма для fuzzy-сравнения). */
    protected static array $cyrToLat = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e',
        'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'i', 'к' => 'k',
        'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
        'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        // казахские
        'ә' => 'a', 'ғ' => 'g', 'қ' => 'k', 'ң' => 'n', 'ө' => 'o',
        'ұ' => 'u', 'ү' => 'u', 'һ' => 'h', 'і' => 'i',
    ];

    /**
     * Раскладка: символ, набранный в "не той" раскладке → правильный.
     * Латинская клавиша (QWERTY) → кириллица (ЙЦУКЕН) и наоборот.
     */
    protected static array $latToCyrKey = [
        'q' => 'й', 'w' => 'ц', 'e' => 'у', 'r' => 'к', 't' => 'е', 'y' => 'н',
        'u' => 'г', 'i' => 'ш', 'o' => 'щ', 'p' => 'з', '[' => 'х', ']' => 'ъ',
        'a' => 'ф', 's' => 'ы', 'd' => 'в', 'f' => 'а', 'g' => 'п', 'h' => 'р',
        'j' => 'о', 'k' => 'л', 'l' => 'д', ';' => 'ж', "'" => 'э',
        'z' => 'я', 'x' => 'ч', 'c' => 'с', 'v' => 'м', 'b' => 'и', 'n' => 'т',
        'm' => 'ь', ',' => 'б', '.' => 'ю',
    ];

    protected static ?array $cyrToLatKey = null;

    public static function toLatin(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $out = '';
        $len = mb_strlen($s, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($s, $i, 1, 'UTF-8');
            $out .= self::$cyrToLat[$ch] ?? $ch;
        }
        return $out;
    }

    /** Латиница, набранная в кириллической раскладке → кириллица. */
    public static function fixLayoutLatToCyr(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $out = '';
        $len = mb_strlen($s, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($s, $i, 1, 'UTF-8');
            $out .= self::$latToCyrKey[$ch] ?? $ch;
        }
        return $out;
    }

    /** Кириллица, набранная в латинской раскладке → латиница. */
    public static function fixLayoutCyrToLat(string $s): string
    {
        if (self::$cyrToLatKey === null) {
            self::$cyrToLatKey = array_flip(self::$latToCyrKey);
        }
        $s = mb_strtolower($s, 'UTF-8');
        $out = '';
        $len = mb_strlen($s, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($s, $i, 1, 'UTF-8');
            $out .= self::$cyrToLatKey[$ch] ?? $ch;
        }
        return $out;
    }

    public static function hasCyrillic(string $s): bool
    {
        return (bool) preg_match('/\p{Cyrillic}/u', $s);
    }

    public static function hasLatin(string $s): bool
    {
        return (bool) preg_match('/[a-z]/i', $s);
    }
}
