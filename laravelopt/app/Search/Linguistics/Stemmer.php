<?php

namespace App\Search\Linguistics;

/**
 * Лёгкий стеммер: приводит словоформы к основе, чтобы "камеры", "камерой",
 * "камер" матчили "камера". Русская часть — на базе алгоритма Портера/Snowball
 * (упрощённо), английская — отсечение типовых окончаний. Без ИИ.
 */
class Stemmer
{
    protected const VOWEL = 'аеиоуыэюяё';

    public static function stem(string $word): string
    {
        $word = mb_strtolower($word, 'UTF-8');

        if (preg_match('/\p{Cyrillic}/u', $word)) {
            return self::stemRu($word);
        }
        if (preg_match('/[a-z]/', $word)) {
            return self::stemEn($word);
        }
        return $word; // числа, артикулы — как есть
    }

    /* ---------------- Русский (Porter/Snowball, упрощённо) ---------------- */

    protected static function stemRu(string $w): string
    {
        $w = str_replace('ё', 'е', $w);
        if (mb_strlen($w, 'UTF-8') <= 3) {
            return $w;
        }

        // RV — область после первой гласной
        if (! preg_match('/^(.*?[' . self::VOWEL . '])(.*)$/u', $w, $m)) {
            return $w;
        }
        $pre = $m[1];
        $rv = $m[2];

        // 1) PERFECTIVE GERUND (в/вши/вшись только после а или я)
        $rv2 = preg_replace('/(?<=[ая])(в|вши|вшись)$/u', '', $rv, 1, $cnt);
        if ($cnt) {
            return self::finalizeRu($pre, $rv2);
        }
        $rv2 = preg_replace('/(ив|ивши|ившись|ыв|ывши|ывшись)$/u', '', $rv, 1, $cnt);
        if ($cnt) {
            return self::finalizeRu($pre, $rv2);
        }
        // REFLEXIVE
        $rv = preg_replace('/(ся|сь)$/u', '', $rv);

        // 2) ADJECTIVE / PARTICIPLE / VERB / NOUN endings
        // Набор настроен под каталог: приоритет существительных, убраны
        // короткие глагольные окончания (н/л/ли/ет...), конфликтующие с
        // формами существительных и согласными на конце основы.
        $patterns = [
            // прилагательные
            '/(ее|ие|ые|ое|ими|ыми|ей|ий|ый|ой|ем|им|ым|ом|его|ого|ему|ому|их|ых|ую|юю|ая|яя|ою|ею)$/u',
            // причастия
            '/(ивш|ывш|ующ|нн|вш|ющ|щ)$/u',
            // глаголы (только отчётливые окончания)
            '/(уйте|ейте|ует|уют|ишь|ешь|нно|ить|ыть|ать|еть|ите|или|ыли|ила|ыла|ено|ена|уй|ишься|ется|иться)$/u',
            // существительные
            '/(иями|ями|ами|ев|ов|ие|ье|иям|ям|ием|ем|ам|ом|ах|иях|ях|ию|ью|ия|ья|еи|ии|ий|ой|ей|а|е|и|й|о|у|ы|ь|ю|я)$/u',
        ];
        foreach ($patterns as $p) {
            $new = preg_replace($p, '', $rv, 1, $cnt);
            if ($cnt) {
                $rv = $new;
                break;
            }
        }

        return self::finalizeRu($pre, $rv);
    }

    protected static function finalizeRu(string $pre, string $rv): string
    {
        // и -> удалить, удвоенную нн -> н, ь -> удалить, superlative
        $rv = preg_replace('/и$/u', '', $rv);
        $rv = preg_replace('/(ейш|ейше)$/u', '', $rv);
        $rv = preg_replace('/нн$/u', 'н', $rv);
        $rv = preg_replace('/ь$/u', '', $rv);
        $stem = $pre . $rv;

        return mb_strlen($stem, 'UTF-8') >= 2 ? $stem : ($pre . $rv);
    }

    /* ---------------- Английский (lite) ---------------- */

    protected static function stemEn(string $w): string
    {
        if (strlen($w) <= 3) {
            return $w;
        }
        $w = preg_replace('/(ically|ation|ities|ousness|iveness|fulness)$/', '', $w, 1, $c);
        if ($c) return $w;
        $w = preg_replace('/(ing|edly|edness)$/', '', $w, 1, $c);
        if ($c && strlen($w) >= 3) return self::deDouble($w);
        $w = preg_replace('/(ies)$/', 'y', $w, 1, $c);
        if ($c) return $w;
        $w = preg_replace('/(ed|es|ly|er|or)$/', '', $w, 1, $c);
        if ($c && strlen($w) >= 3) return $w;
        $w = preg_replace('/s$/', '', $w, 1, $c);

        return $w;
    }

    protected static function deDouble(string $w): string
    {
        // running -> runn -> run
        if (preg_match('/(.)\1$/', $w) && ! preg_match('/(ss|ll|zz)$/', $w)) {
            return substr($w, 0, -1);
        }
        return $w;
    }
}
