<?php

namespace App\Search\Linguistics;

/**
 * Нечёткое сравнение строк: расстояние Левенштейна (мультибайтовое) и
 * сходство по триграммам. Используется для исправления опечаток и
 * частичных совпадений. Без ИИ.
 */
class Fuzzy
{
    /** Мультибайтовое расстояние Левенштейна (работает с кириллицей). */
    public static function distance(string $a, string $b): int
    {
        if ($a === $b) return 0;
        $la = mb_strlen($a, 'UTF-8');
        $lb = mb_strlen($b, 'UTF-8');
        if ($la === 0) return $lb;
        if ($lb === 0) return $la;

        // ASCII-only? — используем нативный levenshtein (быстрее)
        if (! preg_match('/[^\x00-\x7F]/', $a . $b)) {
            return levenshtein($a, $b);
        }

        $ca = self::chars($a);
        $cb = self::chars($b);
        $prev = range(0, $lb);
        $curr = array_fill(0, $lb + 1, 0);

        for ($i = 1; $i <= $la; $i++) {
            $curr[0] = $i;
            for ($j = 1; $j <= $lb; $j++) {
                $cost = ($ca[$i - 1] === $cb[$j - 1]) ? 0 : 1;
                $curr[$j] = min(
                    $prev[$j] + 1,
                    $curr[$j - 1] + 1,
                    $prev[$j - 1] + $cost
                );
            }
            $prev = $curr;
        }

        return $prev[$lb];
    }

    /** Похожесть 0..1 на основе Левенштейна. */
    public static function ratio(string $a, string $b): float
    {
        $max = max(mb_strlen($a, 'UTF-8'), mb_strlen($b, 'UTF-8'));
        if ($max === 0) return 1.0;
        return 1.0 - self::distance($a, $b) / $max;
    }

    /** Набор триграмм слова (с маркерами границ). */
    public static function trigrams(string $s): array
    {
        $s = '  ' . $s . ' ';
        $out = [];
        $len = mb_strlen($s, 'UTF-8');
        for ($i = 0; $i < $len - 2; $i++) {
            $out[mb_substr($s, $i, 3, 'UTF-8')] = true;
        }
        return array_keys($out);
    }

    /** Сходство по триграммам (коэффициент Жаккара) 0..1. */
    public static function trigramSimilarity(string $a, string $b): float
    {
        $ta = self::trigrams($a);
        $tb = self::trigrams($b);
        if (empty($ta) || empty($tb)) return 0.0;
        $inter = count(array_intersect($ta, $tb));
        $union = count(array_unique(array_merge($ta, $tb)));
        return $union ? $inter / $union : 0.0;
    }

    protected static function chars(string $s): array
    {
        return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    }
}
