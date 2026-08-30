<?php

namespace App\Search;

use App\Search\Linguistics\Tokenizer;
use App\Search\Linguistics\Transliterator;

/**
 * Разбирает пользовательский запрос в одну или несколько интерпретаций:
 * прямую и с исправленной раскладкой клавиатуры. Поиск пробует все и
 * берёт наилучшую по score.
 */
class QueryParser
{
    /** @return array<int, array{phrase:string, tokens:array, translit:array, penalty:float, note:?string}> */
    public static function parse(string $q): array
    {
        $interps = [];
        $seen = [];

        $add = function (string $phrase, float $penalty, ?string $note) use (&$interps, &$seen) {
            $phrase = Tokenizer::normalize($phrase);
            if ($phrase === '' || isset($seen[$phrase])) return;
            $tokens = Tokenizer::tokenize($phrase);
            if (empty($tokens)) return;
            $seen[$phrase] = true;
            $interps[] = [
                'phrase' => $phrase,
                'tokens' => $tokens,
                'translit' => Tokenizer::translitTokens($tokens),
                'penalty' => $penalty,
                'note' => $note,
            ];
        };

        $norm = Tokenizer::normalize($q);

        // 1) Прямая интерпретация
        $add($norm, 1.0, null);

        // 2) Исправление раскладки: латиница, набранная в RU-раскладке → кириллица
        if (Transliterator::hasLatin($norm) && ! Transliterator::hasCyrillic($norm)) {
            $fixed = Transliterator::fixLayoutLatToCyr($norm);
            $add($fixed, 0.97, 'layout:en→ru');
        }

        // 3) Кириллица, набранная в EN-раскладке → латиница (бренды)
        if (Transliterator::hasCyrillic($norm)) {
            $fixed = Transliterator::fixLayoutCyrToLat($norm);
            // имеет смысл, только если получилось осмысленное латинское слово
            if (preg_match('/[a-z]{3,}/', $fixed)) {
                $add($fixed, 0.9, 'layout:ru→en');
            }
        }

        return $interps;
    }
}
