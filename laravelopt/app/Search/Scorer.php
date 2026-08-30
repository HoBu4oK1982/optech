<?php

namespace App\Search;

use App\Search\Linguistics\Fuzzy;
use App\Search\Linguistics\Tokenizer;
use App\Search\Linguistics\Transliterator;

/**
 * Оценивает релевантность документа интерпретации запроса. Учитывает тип
 * совпадения (точное/префикс/опечатка/триграмма), вес поля, покрытие
 * токенов, фразовые бонусы, ручные приоритеты и бизнес-бусты.
 */
class Scorer
{
    /**
     * @param array $interp интерпретация из QueryParser
     * @param array $doc    документ (поля из search_documents + tokens/translit)
     * @return array{score:float, matched:int, total:int, how:array, max_field_weight:float, body_only:bool, requires_body:bool, matched_fields:array, manual_boost:float}
     */
    public static function score(array $interp, array $doc, array $opts = []): array
    {
        $ms = config('search.match_scores', []);
        $boosts = config('search.boosts', []);
        $fuzzyCfg = config('search.fuzzy', []);
        $fieldWeights = config('search.field_weights', []);
        $bodyWeight = (float) ($fieldWeights['body'] ?? 0.35);
        $strictMode = ($opts['mode'] ?? null) === 'suggest';

        $tokenBundle = self::tokensForMode($doc, $opts);
        $docTokens = $tokenBundle['tokens'];
        $docTranslit = $tokenBundle['translit'];
        $tokenFields = $tokenBundle['token_fields'];
        $translitFields = $tokenBundle['translit_fields'];

        $qTokens = $interp['tokens'];
        $qTranslit = $interp['translit'];
        $docTokenList = array_keys($docTokens);

        $sum = 0.0;
        $matched = 0;
        $how = [];
        $maxFieldWeight = 0.0;
        $matchedFields = [];
        $requiresBody = false;

        foreach ($qTokens as $idx => $qt) {
            $qtr = $qTranslit[$idx] ?? Transliterator::toLatin($qt);
            $best = 0.0;
            $bestHow = null;
            $bestFieldWeight = 0.0;
            $bestField = null;
            $bestToken = null;

            // 1) точное совпадение основы
            if (isset($docTokens[$qt])) {
                $bestFieldWeight = (float) $docTokens[$qt];
                $best = ((float) ($ms['exact'] ?? 1.0)) * $bestFieldWeight;
                $bestHow = 'exact';
                $bestToken = $qt;
                $bestField = self::bestFieldForToken($tokenFields, $qt, $bestFieldWeight);
            } else {
                // 2) префиксное совпадение: "бумаж" -> "бумажные".
                $maxDist = self::maxDistance(mb_strlen($qt, 'UTF-8'), $fuzzyCfg);
                foreach ($docTokenList as $dt) {
                    $fw = (float) $docTokens[$dt];

                    if (mb_strlen($qt, 'UTF-8') >= 2 &&
                        (str_starts_with($dt, $qt) || str_starts_with($qt, $dt))) {
                        $cand = ((float) ($ms['prefix'] ?? 0.65)) * $fw;
                        if ($cand > $best) {
                            $best = $cand;
                            $bestHow = 'prefix';
                            $bestFieldWeight = $fw;
                            $bestToken = $dt;
                            $bestField = self::bestFieldForToken($tokenFields, $dt, $fw);
                        }
                        continue;
                    }
                }

                // 3) fuzzy по транслиту (кросс-алфавит): сравниваем латинские формы
                if ($bestHow === null || $best < ((float) ($ms['fuzzy'] ?? 0.45))) {
                    foreach ($docTranslit as $dtr => $fw) {
                        $fw = (float) $fw;
                        $lenDiff = abs(mb_strlen($qtr, 'UTF-8') - mb_strlen($dtr, 'UTF-8'));
                        if ($lenDiff > $maxDist + 1) continue;
                        $dist = Fuzzy::distance($qtr, $dtr);
                        if ($dist <= $maxDist) {
                            $cand = ((float) ($ms['fuzzy'] ?? 0.45)) * $fw * (1 - $dist / ($maxDist + 1));
                            if ($cand > $best) {
                                $best = $cand;
                                $bestHow = 'fuzzy';
                                $bestFieldWeight = $fw;
                                $bestToken = $dtr;
                                $bestField = self::bestFieldForToken($translitFields, $dtr, $fw);
                            }
                        }
                    }
                }

                // 4) триграммы (частичное)
                if ($bestHow === null) {
                    $bestSim = 0.0;
                    $bestFw = 0.0;
                    $bestDtr = null;
                    foreach ($docTranslit as $dtr => $fw) {
                        $fw = (float) $fw;
                        $sim = Fuzzy::trigramSimilarity($qtr, $dtr);
                        if ($sim > $bestSim) {
                            $bestSim = $sim;
                            $bestFw = $fw;
                            $bestDtr = $dtr;
                        }
                    }
                    if ($bestSim >= ($fuzzyCfg['trigram_threshold'] ?? 0.34)) {
                        $best = ((float) ($ms['trigram'] ?? 0.3)) * $bestFw * $bestSim;
                        $bestHow = 'trigram';
                        $bestFieldWeight = $bestFw;
                        $bestToken = $bestDtr;
                        $bestField = self::bestFieldForToken($translitFields, (string) $bestDtr, $bestFw);
                    }
                }
            }

            if ($best > 0) {
                $field = $bestField ?: ($bestFieldWeight <= ($bodyWeight + 0.0001) ? 'body' : 'unknown');
                $sum += $best;
                $matched++;
                $maxFieldWeight = max($maxFieldWeight, $bestFieldWeight);
                $matchedFields[$field] = true;
                if ($field === 'body') {
                    $requiresBody = true;
                }

                $how[$qt] = [
                    'kind' => $bestHow,
                    'field' => $field,
                    'matched_token' => $bestToken,
                    'field_weight' => round($bestFieldWeight, 3),
                ];
            }
        }

        $total = count($qTokens);
        if ($total === 0 || $matched === 0) {
            return self::zeroScore($total);
        }

        // Покрытие токенов: документы со всеми словами запроса выигрывают, но
        // частичное совпадение всё равно возвращается, а не проваливает функцию.
        $coverage = $matched / $total;
        $score = $sum * (0.25 + 0.75 * $coverage);

        // Фразовые бонусы. В suggest не даём body-фразе поднимать мусорный товар.
        $phrase = $interp['phrase'];
        $normTitle = $doc['norm_title'] ?? '';
        $normStrict = $doc['norm_strict'] ?? '';
        $normAll = $doc['norm_all'] ?? '';

        if ($phrase !== '' && $normTitle === $phrase) {
            $score += (float) ($boosts['exact_title'] ?? 14.0);
        }

        if ($phrase !== '' && str_contains($normTitle, $phrase)) {
            $score += (float) ($boosts['exact_phrase_in_title'] ?? 10.0);
            if (str_starts_with($normTitle, $phrase)) {
                $score += (float) ($boosts['title_starts_with'] ?? 5.0);
            }
        } elseif ($phrase !== '' && $normStrict !== '' && str_contains($normStrict, $phrase)) {
            $score += (float) ($boosts['exact_phrase_in_strict'] ?? 4.0);
            if (str_starts_with($normStrict, $phrase)) {
                $score += (float) ($boosts['strict_starts_with'] ?? 2.0);
            }
        } elseif (! $strictMode && $phrase !== '' && str_contains($normAll, $phrase)) {
            // body-фраза полезна на полной странице, но не должна перебивать title.
            $score += ((float) ($boosts['exact_phrase_in_title'] ?? 10.0)) * 0.2;
        }

        if ($matched === $total) {
            $score += (float) ($boosts['all_tokens'] ?? 3.0);
        }

        // На полной странице поиска товар, найденный только по длинному описанию,
        // не должен становиться первым и создавать "мусорную" выдачу.
        $fields = array_keys($matchedFields);
        $bodyOnly = count($fields) === 1 && in_array('body', $fields, true);

        if (($opts['mode'] ?? null) === 'search' && $bodyOnly) {
            $score *= (float) config('search.full.body_only_multiplier', 0.12);
        }

        // бизнес-бусты
        if (! empty($doc['in_stock'])) {
            $score += (float) ($boosts['in_stock'] ?? 1.5);
        }
        $score += ((float) ($boosts['popularity_max'] ?? 3.0)) * min(1.0, (float) ($doc['popularity'] ?? 0));

        // ручные приоритеты для коммерчески важных направлений.
        $manualBoost = self::manualPriorityBoost($phrase, $doc);
        $score += $manualBoost;

        // вес типа сущности и штраф интерпретации (раскладка)
        $score *= (float) ($doc['weight'] ?? 1.0);
        $score *= (float) ($interp['penalty'] ?? 1.0);

        return [
            'score' => $score,
            'matched' => $matched,
            'total' => $total,
            'how' => $how,
            'max_field_weight' => round($maxFieldWeight, 3),
            'body_only' => $bodyOnly,
            'requires_body' => $requiresBody,
            'matched_fields' => $fields,
            'manual_boost' => round($manualBoost, 3),
        ];
    }

    protected static function tokensForMode(array $doc, array $opts): array
    {
        $strictFields = (array) ($opts['strict_fields'] ?? []);
        if (($opts['mode'] ?? null) === 'suggest' && empty($strictFields)) {
            $strictFields = (array) config('search.suggest.strict_fields', ['title', 'keywords']);
        }

        if (($opts['mode'] ?? null) === 'suggest' && ! empty($strictFields)) {
            $tokenFields = (array) ($doc['token_fields'] ?? []);
            $translitFields = (array) ($doc['translit_fields'] ?? []);

            if (! empty($tokenFields)) {
                return [
                    'tokens' => self::filterFieldMapToWeights($tokenFields, $strictFields),
                    'translit' => self::filterFieldMapToWeights($translitFields, $strictFields),
                    'token_fields' => self::filterFieldMap($tokenFields, $strictFields),
                    'translit_fields' => self::filterFieldMap($translitFields, $strictFields),
                ];
            }

            // Если миграция уже применена, но search:reindex ещё не запускали,
            // token_fields пустые. В этом случае не убиваем поиск в шапке:
            // восстанавливаем строгий набор хотя бы из title/norm_strict.
            return self::fallbackStrictTokenBundle($doc);
        }

        return [
            'tokens' => (array) ($doc['tokens'] ?? []),
            'translit' => (array) ($doc['translit'] ?? []),
            'token_fields' => (array) ($doc['token_fields'] ?? []),
            'translit_fields' => (array) ($doc['translit_fields'] ?? []),
        ];
    }

    protected static function fallbackStrictTokenBundle(array $doc): array
    {
        $fw = config('search.field_weights', []);
        $titleWeight = (float) ($fw['title'] ?? 8.0);
        $keywordWeight = (float) ($fw['keywords'] ?? 4.0);

        $titleText = (string) ($doc['title'] ?? $doc['norm_title'] ?? '');
        $strictText = trim((string) ($doc['norm_strict'] ?? ''));

        $titleTokens = Tokenizer::tokenize($titleText);
        $strictTokensRaw = $strictText !== '' ? Tokenizer::tokenize($strictText) : $titleTokens;

        $tokens = [];
        $tokenFields = [];

        foreach ($strictTokensRaw as $token) {
            $tokens[$token] = max($tokens[$token] ?? 0, $keywordWeight);
            $tokenFields[$token]['keywords'] = max($tokenFields[$token]['keywords'] ?? 0, $keywordWeight);
        }
        foreach ($titleTokens as $token) {
            $tokens[$token] = max($tokens[$token] ?? 0, $titleWeight);
            $tokenFields[$token]['title'] = max($tokenFields[$token]['title'] ?? 0, $titleWeight);
        }

        $translit = [];
        $translitFields = [];
        foreach ($tokenFields as $token => $fields) {
            $latin = Transliterator::toLatin((string) $token);
            foreach ((array) $fields as $field => $weight) {
                $translit[$latin] = max($translit[$latin] ?? 0, (float) $weight);
                $translitFields[$latin][$field] = max($translitFields[$latin][$field] ?? 0, (float) $weight);
            }
        }

        return [
            'tokens' => $tokens,
            'translit' => $translit,
            'token_fields' => $tokenFields,
            'translit_fields' => $translitFields,
        ];
    }

    protected static function filterFieldMapToWeights(array $fieldMap, array $allowedFields): array
    {
        $filtered = [];
        $allowed = array_fill_keys($allowedFields, true);

        foreach ($fieldMap as $token => $fields) {
            foreach ((array) $fields as $field => $weight) {
                if (isset($allowed[$field])) {
                    $filtered[$token] = max($filtered[$token] ?? 0, (float) $weight);
                }
            }
        }

        return $filtered;
    }

    protected static function filterFieldMap(array $fieldMap, array $allowedFields): array
    {
        $filtered = [];
        $allowed = array_fill_keys($allowedFields, true);

        foreach ($fieldMap as $token => $fields) {
            foreach ((array) $fields as $field => $weight) {
                if (isset($allowed[$field])) {
                    $filtered[$token][$field] = (float) $weight;
                }
            }
        }

        return $filtered;
    }

    protected static function bestFieldForToken(array $fieldMap, string $token, float $targetWeight): ?string
    {
        $fields = (array) ($fieldMap[$token] ?? []);
        if (empty($fields)) return null;

        $bestField = null;
        $bestWeight = -1.0;
        foreach ($fields as $field => $weight) {
            $weight = (float) $weight;
            if ($weight > $bestWeight || (abs($weight - $targetWeight) < 0.0001 && $bestField === null)) {
                $bestWeight = $weight;
                $bestField = (string) $field;
            }
        }

        return $bestField;
    }

    protected static function zeroScore(int $total): array
    {
        return [
            'score' => 0.0,
            'matched' => 0,
            'total' => $total,
            'how' => [],
            'max_field_weight' => 0.0,
            'body_only' => false,
            'requires_body' => false,
            'matched_fields' => [],
            'manual_boost' => 0.0,
        ];
    }

    protected static function maxDistance(int $len, array $cfg): int
    {
        if ($len < ($cfg['min_word_len'] ?? 3)) return 0;
        foreach (($cfg['distance_by_len'] ?? []) as $maxLen => $dist) {
            if ($len <= $maxLen) return (int) $dist;
        }
        return 2;
    }

    protected static function manualPriorityBoost(string $phrase, array $doc): float
    {
        $rules = (array) config('search.manual_priorities', []);
        if (empty($rules)) return 0.0;

        $normPhrase = Tokenizer::normalize($phrase);
        $normTitle = Tokenizer::normalize((string) ($doc['norm_title'] ?? $doc['title'] ?? ''));
        $normUrl = Tokenizer::normalize(str_replace(['/', '-', '_'], ' ', (string) ($doc['url'] ?? '')));
        $docType = (string) ($doc['type'] ?? '');
        $docId = (int) ($doc['searchable_id'] ?? 0);

        $best = 0.0;

        foreach ($rules as $rule) {
            if (! self::ruleQueryMatches($normPhrase, (array) ($rule['query'] ?? []))) {
                continue;
            }

            if (isset($rule['type'])) {
                $types = is_array($rule['type']) ? $rule['type'] : [$rule['type']];
                if (! in_array($docType, $types, true)) continue;
            }

            if (isset($rule['id'])) {
                $ids = is_array($rule['id']) ? $rule['id'] : [$rule['id']];
                $ids = array_map('intval', $ids);
                if (! in_array($docId, $ids, true)) continue;
            }

            $hasTargetCondition = isset($rule['title_contains']) || isset($rule['url_contains']);
            $targetMatched = ! $hasTargetCondition;

            foreach ((array) ($rule['title_contains'] ?? []) as $needle) {
                $needle = Tokenizer::normalize((string) $needle);
                if ($needle !== '' && str_contains($normTitle, $needle)) {
                    $targetMatched = true;
                    break;
                }
            }

            if (! $targetMatched) {
                foreach ((array) ($rule['url_contains'] ?? []) as $needle) {
                    $needle = Tokenizer::normalize((string) $needle);
                    if ($needle !== '' && str_contains($normUrl, $needle)) {
                        $targetMatched = true;
                        break;
                    }
                }
            }

            if (! $targetMatched) continue;

            $best = max($best, (float) ($rule['boost'] ?? 0));
        }

        return $best;
    }

    protected static function ruleQueryMatches(string $normPhrase, array $queries): bool
    {
        if ($normPhrase === '') return false;

        foreach ($queries as $q) {
            $q = Tokenizer::normalize((string) $q);
            if ($q === '') continue;
            if (str_contains($normPhrase, $q) || str_contains($q, $normPhrase)) {
                return true;
            }
        }

        return false;
    }
}
