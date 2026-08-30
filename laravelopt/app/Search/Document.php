<?php

namespace App\Search;

use App\Search\Linguistics\Tokenizer;
use App\Search\Linguistics\Transliterator;

/**
 * Сборка поискового документа из полей сущности. Один и тот же код
 * используется индексатором (запись в БД) и поиском (реконструкция из БД),
 * чтобы пространство токенов запроса и документа совпадало.
 */
class Document
{
    /** Максимальная длина текста тела, попадающего в индекс (символов). */
    protected const BODY_LIMIT = 8000;

    /**
     * Построить индексные поля из title/keywords/body.
     * Возвращает массив, пригодный для сохранения в search_documents.
     */
    public static function build(string $title, string $keywords = '', string $body = ''): array
    {
        $fw = config('search.field_weights', []);
        $titleWeight = (float) ($fw['title'] ?? 8.0);
        $keywordsWeight = (float) ($fw['keywords'] ?? 4.0);
        $bodyWeight = (float) ($fw['body'] ?? 0.35);

        // Чистим HTML/стили из полей и ограничиваем объём тела, чтобы в индекс
        // шёл текст, а не разметка (иначе попадает "p style margin font-family ...").
        $title = self::stripHtml($title);
        $keywords = self::stripHtml($keywords);
        $body = mb_substr(self::stripHtml($body), 0, self::BODY_LIMIT, 'UTF-8');

        $titleTokens = Tokenizer::tokenize($title);
        $keywordTokens = Tokenizer::tokenize($keywords);
        $bodyTokens = Tokenizer::tokenize($body);

        // token => максимальный вес поля, где он встречается
        $tokens = [];
        $tokenFields = [];
        self::addFieldTokens($tokens, $tokenFields, $bodyTokens, 'body', $bodyWeight);
        self::addFieldTokens($tokens, $tokenFields, $keywordTokens, 'keywords', $keywordsWeight);
        self::addFieldTokens($tokens, $tokenFields, $titleTokens, 'title', $titleWeight);

        // Строгий индекс для шапки: title + keywords.
        // В keywords у товаров кладём SKU, brand, category и meta_keywords.
        $strictTokens = [];
        foreach ($keywordTokens as $token) {
            $strictTokens[$token] = max($strictTokens[$token] ?? 0, $keywordsWeight);
        }
        foreach ($titleTokens as $token) {
            $strictTokens[$token] = max($strictTokens[$token] ?? 0, $titleWeight);
        }

        $translit = self::translitWeighted($tokens);
        $strictTranslit = self::translitWeighted($strictTokens);
        $translitFields = self::translitFieldMap($tokenFields);

        return [
            'norm_title' => Tokenizer::normalize($title),
            'norm_strict' => Tokenizer::normalize(trim($title . ' ' . $keywords)),
            'norm_all' => Tokenizer::normalize(trim($title . ' ' . $keywords . ' ' . $body)),
            'stem_strict' => implode(' ', array_keys($strictTokens)),
            'stem_all' => implode(' ', array_keys($tokens)),
            'translit_strict' => implode(' ', array_keys($strictTranslit)),
            'translit_all' => implode(' ', array_keys($translit)),
            'tokens' => $tokens,
            'translit' => $translit,
            'token_fields' => $tokenFields,
            'translit_fields' => $translitFields,
            'strict_tokens' => $strictTokens,
            'strict_translit' => $strictTranslit,
            'title_tokens' => array_fill_keys($titleTokens, $titleWeight),
            'keyword_tokens' => array_fill_keys($keywordTokens, $keywordsWeight),
            'body_tokens' => array_fill_keys($bodyTokens, $bodyWeight),
        ];
    }

    protected static function addFieldTokens(array &$tokens, array &$tokenFields, array $fieldTokens, string $field, float $weight): void
    {
        foreach ($fieldTokens as $token) {
            $tokens[$token] = max($tokens[$token] ?? 0, $weight);
            $tokenFields[$token][$field] = max($tokenFields[$token][$field] ?? 0, $weight);
        }
    }

    protected static function translitWeighted(array $tokens): array
    {
        $translit = [];
        foreach ($tokens as $token => $weight) {
            $latin = Transliterator::toLatin((string) $token);
            $translit[$latin] = max($translit[$latin] ?? 0, (float) $weight);
        }
        return $translit;
    }

    protected static function translitFieldMap(array $tokenFields): array
    {
        $out = [];
        foreach ($tokenFields as $token => $fields) {
            $latin = Transliterator::toLatin((string) $token);
            foreach ((array) $fields as $field => $weight) {
                $out[$latin][$field] = max($out[$latin][$field] ?? 0, (float) $weight);
            }
        }
        return $out;
    }

    /** Удаляет HTML-теги, стили, скрипты и декодирует сущности. */
    protected static function stripHtml(string $s): string
    {
        if ($s === '' || ! str_contains($s, '<')) {
            // не HTML — только декодируем сущности (&nbsp; и т.п.)
            return html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        // вырезаем содержимое script/style целиком
        $s = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $s);
        // теги -> пробел
        $s = preg_replace('#<[^>]+>#', ' ', $s);
        // сущности -> символы, неразрывный пробел -> обычный
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = str_replace("\xC2\xA0", ' ', $s);
        // схлопываем пробелы
        return trim(preg_replace('/\s+/u', ' ', $s));
    }
}
