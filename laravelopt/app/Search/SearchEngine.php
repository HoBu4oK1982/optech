<?php

namespace App\Search;

use App\Search\Linguistics\Fuzzy;
use App\Search\Linguistics\Tokenizer;

/**
 * Поисковый движок: разбирает запрос, получает кандидатов из репозитория,
 * ранжирует их скорером, формирует результат, подсказки и исправление опечаток.
 *
 * Репозиторий должен реализовывать методы:
 *   candidates(array $stems, array $prefixes, ?string $type, string $locale, int $limit): array
 *   vocabulary(string $locale): array   // список поверхностных слов (для коррекции/подсказок)
 */
class SearchEngine
{
    public function __construct(protected $repo) {}

    public function search(string $q, array $opts = []): array
    {
        $locale = $opts['locale'] ?? 'ru';
        $type = $opts['type'] ?? null;
        $limit = (int) ($opts['limit'] ?? 20);

        $interps = QueryParser::parse($q);
        if (empty($interps)) {
            return $this->emptyResult($q);
        }

        $docs = $this->fetchCandidates($interps, $type, $locale, $opts);

        $scored = [];
        foreach ($docs as $doc) {
            $best = 0.0;
            $bestInfo = null;
            foreach ($interps as $interp) {
                $r = Scorer::score($interp, $doc, $opts);
                if ($r['score'] > $best) {
                    $best = $r['score'];
                    $bestInfo = $r;
                }
            }
            if ($best > 0 && $bestInfo && $this->passesModeFilters($doc, $bestInfo, $opts)) {
                $doc['_score'] = round($best, 3);
                $doc['_match'] = $bestInfo;
                $scored[] = $doc;
            }
        }

        usort($scored, fn ($a, $b) => $this->compareResults($a, $b));

        $top = array_slice($scored, 0, $limit);

        // исправление опечаток, если результатов мало или совпадение неполное
        $correction = null;
        $primary = $interps[0];
        $needCorrection = empty($scored)
            || (($scored[0]['_match']['matched'] ?? 0) < ($primary['total'] ?? count($primary['tokens'])));
        if ($needCorrection) {
            $correction = $this->correct($primary, $locale);
        }

        // если ничего не нашли, но есть исправление — ищем по исправленному запросу
        if (empty($top) && $correction && empty($opts['_corrected'])) {
            $rerun = $this->search($correction['query'], array_merge($opts, ['_corrected' => true]));
            $rerun['query'] = $q;
            $rerun['corrected'] = $correction;
            return $rerun;
        }

        return [
            'query' => $q,
            'corrected' => $correction,
            'total' => count($scored),
            'results' => array_map([$this, 'present'], $top),
            'groups' => $this->groupByType($top),
        ];
    }

    /** Мгновенные подсказки (autocomplete). */
    public function suggest(string $q, array $opts = []): array
    {
        $locale = $opts['locale'] ?? 'ru';
        $limit = (int) ($opts['limit'] ?? 8);

        $interps = QueryParser::parse($q);
        if (empty($interps)) return ['query' => $q, 'suggestions' => [], 'products' => []];

        // Для шапки включён более строгий режим: товары, найденные только по
        // длинному описанию, не попадают в dropdown и не создают визуальный шум.
        $res = $this->search($q, [
            'locale' => $locale,
            'limit' => $limit,
            'mode' => 'suggest',
            'strict_fields' => (array) config('search.suggest.strict_fields', ['title', 'keywords']),
        ]);

        $suggestions = [];
        foreach ($res['results'] as $r) {
            $suggestions[] = [
                'text' => $r['title'],
                'type' => $r['type'],
                'url' => $r['url'],
                'score' => $r['score'] ?? 0,
            ];
        }

        return [
            'query' => $q,
            'corrected' => $res['corrected'],
            'suggestions' => $suggestions,
            'products' => array_values(array_filter($res['results'], fn ($r) => $r['type'] === 'product')),
        ];
    }

    protected function passesModeFilters(array $doc, array $match, array $opts): bool
    {
        $mode = $opts['mode'] ?? null;

        if ($mode === 'suggest') {
            $cfg = (array) config('search.suggest', []);
            $score = (float) ($match['score'] ?? 0);
            $minScore = (float) ($cfg['min_score'] ?? 0);
            if ($minScore > 0 && $score < $minScore) {
                return false;
            }

            $hiddenBodyOnlyTypes = (array) ($cfg['hide_body_only_types'] ?? []);
            if (($match['body_only'] ?? false) && in_array((string) ($doc['type'] ?? ''), $hiddenBodyOnlyTypes, true)) {
                return false;
            }

            if (($cfg['strict_drop_body_matches'] ?? true) && ($match['requires_body'] ?? false)) {
                return false;
            }

            return true;
        }

        if ($mode === 'search') {
            $cfg = (array) config('search.full', []);
            $score = (float) ($match['score'] ?? 0);
            $minScore = (float) ($cfg['min_score'] ?? 0);
            if ($minScore > 0 && $score < $minScore) {
                return false;
            }

            $hiddenBodyOnlyTypes = (array) ($cfg['hide_body_only_types'] ?? []);
            if (($match['body_only'] ?? false) && in_array((string) ($doc['type'] ?? ''), $hiddenBodyOnlyTypes, true)) {
                return false;
            }
        }

        return true;
    }

    protected function fetchCandidates(array $interps, ?string $type, string $locale, array $opts = []): array
    {
        $stems = [];
        $prefixes = [];
        foreach ($interps as $interp) {
            foreach ($interp['tokens'] as $t) {
                $stems[$t] = true;
                $prefixes[mb_substr($t, 0, 3, 'UTF-8')] = true;
            }
        }
        $limit = (int) config('search.candidate_limit', 500);

        return $this->repo->candidates(
            array_keys($stems),
            array_keys($prefixes),
            $type,
            $locale,
            $limit,
            $opts
        );
    }

    /** Подбор ближайшего слова из словаря для исправления опечатки. */
    protected function correct(array $interp, string $locale): ?array
    {
        $vocab = $this->repo->vocabulary($locale);
        if (empty($vocab)) return null;

        $changed = false;
        $fixedWords = [];
        foreach (preg_split('/\s+/u', $interp['phrase'], -1, PREG_SPLIT_NO_EMPTY) as $word) {
            if (mb_strlen($word, 'UTF-8') < 3) { $fixedWords[] = $word; continue; }

            $bestTerm = null; $bestSim = 0.0; $bestDist = 99;
            foreach ($vocab as $term) {
                $term = Tokenizer::normalize((string) $term);
                if ($term === '') continue;
                $sim = Fuzzy::trigramSimilarity($word, $term);
                if ($sim < 0.4) continue;
                $dist = Fuzzy::distance($word, $term);
                if ($dist > 2) continue;
                if ($sim > $bestSim || ($sim === $bestSim && $dist < $bestDist)) {
                    $bestSim = $sim; $bestDist = $dist; $bestTerm = $term;
                }
            }
            if ($bestTerm && $bestTerm !== $word) {
                $fixedWords[] = $bestTerm; $changed = true;
            } else {
                $fixedWords[] = $word;
            }
        }

        if (! $changed) return null;
        $phrase = implode(' ', $fixedWords);

        return ['query' => $phrase];
    }

    protected function present(array $doc): array
    {
        return [
            'id' => $doc['searchable_id'] ?? null,
            'type' => $doc['type'] ?? null,
            'title' => $doc['title'] ?? null,
            'url' => $doc['url'] ?? null,
            'image' => $doc['image'] ?? null,
            'price' => $doc['price'] ?? null,
            'currency' => $doc['currency'] ?? null,
            'score' => $doc['_score'] ?? 0,
            'match' => [
                'body_only' => (bool) ($doc['_match']['body_only'] ?? false),
                'requires_body' => (bool) ($doc['_match']['requires_body'] ?? false),
                'matched_fields' => $doc['_match']['matched_fields'] ?? [],
                'manual_boost' => $doc['_match']['manual_boost'] ?? 0,
                'how' => $doc['_match']['how'] ?? [],
            ],
        ];
    }

    protected function groupByType(array $docs): array
    {
        $groups = [];
        foreach ($docs as $d) {
            $groups[$d['type']][] = $this->present($d);
        }
        return $groups;
    }

    /**
     * Сортировка выдачи: сначала релевантность, но с коммерческим порядком
     * типов. Товары не должны проигрывать новостям/статьям при близком score.
     */
    protected function compareResults(array $a, array $b): int
    {
        $scoreA = (float) ($a['_score'] ?? 0);
        $scoreB = (float) ($b['_score'] ?? 0);

        $priority = (array) config('search.type_priority', []);
        $typeA = (string) ($a['type'] ?? '');
        $typeB = (string) ($b['type'] ?? '');
        $priorityA = (int) ($priority[$typeA] ?? 0);
        $priorityB = (int) ($priority[$typeB] ?? 0);

        // Если score отличается не радикально, порядок типов важнее: product,
        // category, solution/service, потом news/articles/projects.
        $maxScore = max($scoreA, $scoreB, 1.0);
        $relativeGap = abs($scoreA - $scoreB) / $maxScore;
        if ($relativeGap <= 0.22 && $priorityA !== $priorityB) {
            return $priorityB <=> $priorityA;
        }

        $scoreCmp = $scoreB <=> $scoreA;
        if ($scoreCmp !== 0) {
            return $scoreCmp;
        }

        return $priorityB <=> $priorityA;
    }

    protected function emptyResult(string $q): array
    {
        return ['query' => $q, 'corrected' => null, 'total' => 0, 'results' => [], 'groups' => []];
    }
}
