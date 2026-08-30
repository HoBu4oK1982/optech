<?php

namespace App\Search\Linguistics;

/**
 * Нормализация текста и токенизация. Превращает сырую строку в набор
 * основ (стеммированных токенов) для индексации и поиска.
 */
class Tokenizer
{
    protected static ?array $synonymMap = null;
    protected static ?array $synonymPhraseMap = null;
    protected static ?array $stopwords = null;

    /** Нормализация: нижний регистр, ё→е, удаление мусора, схлопывание пробелов. */
    public static function normalize(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = str_replace(['ё', 'й'], ['е', 'и'], $s); // ё→е; й→и снижает чувствительность
        // оставляем буквы/цифры/пробел/дефис/точку/плюс (модели: rtx-4070, m.2, usb+)
        $s = preg_replace('/[^\p{L}\p{N}\s\-\.\+]+/u', ' ', $s);
        $s = preg_replace('/\s+/u', ' ', $s);
        return trim($s);
    }

    /**
     * Токенизация: нормализация → фразовые синонимы → слова → синонимы → стоп-слова → основы.
     * Возвращает массив уникальных основ.
     */
    public static function tokenize(string $s): array
    {
        $norm = self::applyPhraseSynonyms(self::normalize($s));
        if ($norm === '') return [];

        $words = preg_split('/[\s\-]+/u', $norm, -1, PREG_SPLIT_NO_EMPTY);
        $stop = self::stopwords();
        $out = [];

        foreach ($words as $w) {
            if (mb_strlen($w, 'UTF-8') < 2 && ! ctype_digit($w)) continue;
            if (isset($stop[$w])) continue;

            // canonicalSynonym может вернуть фразу: "медтруба" -> "медная труба".
            $canonical = self::canonicalSynonym($w);
            $canonicalWords = preg_split('/[\s\-]+/u', $canonical, -1, PREG_SPLIT_NO_EMPTY);

            foreach ($canonicalWords as $cw) {
                if (mb_strlen($cw, 'UTF-8') < 2 && ! ctype_digit($cw)) continue;
                if (isset($stop[$cw])) continue;
                $stem = Stemmer::stem($cw);
                if ($stem !== '') $out[$stem] = true;
            }
        }

        return array_keys($out);
    }

    /** Латинская (транслит) форма каждого токена — для кросс-алфавитного fuzzy. */
    public static function translitTokens(array $tokens): array
    {
        $out = [];
        foreach ($tokens as $t) {
            $out[Transliterator::toLatin($t)] = true;
        }
        return array_keys($out);
    }

    public static function canonicalSynonym(string $word): string
    {
        $word = self::normalize($word);
        $map = self::synonymMap();
        return $map[$word] ?? $word;
    }

    /** Заменяет фразовые синонимы до разбиения на слова. */
    protected static function applyPhraseSynonyms(string $text): string
    {
        if ($text === '') return '';

        foreach (self::synonymPhraseMap() as $phrase => $canonical) {
            if ($phrase === $canonical) continue;
            // Граница: не заменяем внутри другого слова/модели.
            $pattern = '/(?<![\p{L}\p{N}])' . preg_quote($phrase, '/') . '(?![\p{L}\p{N}])/u';
            $text = preg_replace($pattern, $canonical, $text) ?? $text;
        }

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    protected static function synonymMap(): array
    {
        if (self::$synonymMap !== null) return self::$synonymMap;
        $map = [];
        foreach ((array) config('search.synonyms', []) as $group) {
            $canonical = self::normalize((string) ($group[0] ?? ''));
            if ($canonical === '') continue;
            foreach ($group as $w) {
                $word = self::normalize((string) $w);
                if ($word === '') continue;
                $map[$word] = $canonical;
            }
        }
        return self::$synonymMap = $map;
    }

    /** Фразовые синонимы сортируются по длине, чтобы сначала заменить длинные варианты. */
    protected static function synonymPhraseMap(): array
    {
        if (self::$synonymPhraseMap !== null) return self::$synonymPhraseMap;

        $map = self::synonymMap();
        uksort($map, static fn ($a, $b) => mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8'));

        return self::$synonymPhraseMap = $map;
    }

    protected static function stopwords(): array
    {
        if (self::$stopwords !== null) return self::$stopwords;
        return self::$stopwords = array_fill_keys((array) config('search.stopwords', []), true);
    }
}
