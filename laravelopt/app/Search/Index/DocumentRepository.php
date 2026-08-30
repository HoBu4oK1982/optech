<?php

namespace App\Search\Index;

use Illuminate\Support\Facades\Schema;
use App\Models\SearchDocument;
use App\Models\SearchTerm;

/**
 * Доступ к поисковому индексу в БД: подбор кандидатов (грубый отбор LIKE)
 * и словарь терминов для подсказок/исправления опечаток.
 */
class DocumentRepository
{
    protected static ?bool $hasStrictIndexColumns = null;

    /**
     * Кандидаты на доскоринг: строки, где встречается любая основа запроса
     * или её 3-буквенный префикс (в кириллической ИЛИ транслит-форме).
     *
     * В режиме suggest сначала пробуем отдельный strict-индекс title+keywords.
     * Если strict-колонки есть, но ещё не заполнены через search:reindex,
     * автоматически откатываемся на stem_all/translit_all, а Scorer уже
     * отфильтрует body через title/norm_strict fallback. Поэтому шапка не
     * умирает после migrate до переиндексации.
     */
    public function candidates(array $stems, array $prefixes, ?string $type, string $locale, int $limit, array $opts = []): array
    {
        $needles = array_values(array_unique(array_filter(array_merge($stems, $prefixes))));
        if (empty($needles)) {
            return [];
        }

        $strictMode = ($opts['mode'] ?? null) === 'suggest'
            && (bool) config('search.suggest.strict_candidates', true)
            && self::hasStrictIndexColumns();

        if ($strictMode) {
            $rows = $this->queryCandidates($needles, $type, $locale, $limit, 'stem_strict', 'translit_strict');

            // Важный fallback: после применения миграции strict-поля могут быть
            // пустыми до запуска search:reindex. Без этого поиск по "бумаж" в
            // шапке вернёт пусто, хотя товар есть в title старого индекса.
            if ($rows->isNotEmpty()) {
                return $this->presentRows($rows);
            }
        }

        $rows = $this->queryCandidates($needles, $type, $locale, $limit, 'stem_all', 'translit_all');
        return $this->presentRows($rows);
    }

    protected function queryCandidates(array $needles, ?string $type, string $locale, int $limit, string $stemColumn, string $translitColumn)
    {
        $q = SearchDocument::query()->where('locale', $locale);
        if ($type) {
            $q->where('type', $type);
        }

        $q->where(function ($w) use ($needles, $stemColumn, $translitColumn) {
            foreach ($needles as $n) {
                $like = '%' . $this->escapeLike($n) . '%';
                $w->orWhere($stemColumn, 'LIKE', $like)
                  ->orWhere($translitColumn, 'LIKE', $like);
            }
        });

        return $q->orderByDesc('popularity')->limit($limit)->get();
    }

    protected function presentRows($rows): array
    {
        return $rows->map(fn ($r) => [
            'searchable_id' => $r->searchable_id,
            'searchable_type' => $r->searchable_type,
            'type' => $r->type,
            'title' => $r->title,
            'url' => $r->url,
            'image' => $r->image,
            'price' => $r->price,
            'currency' => $r->currency,
            'in_stock' => (bool) $r->in_stock,
            'popularity' => (float) $r->popularity,
            'weight' => (float) $r->weight,
            'norm_title' => $r->norm_title ?? '',
            'norm_strict' => $r->norm_strict ?? '',
            'norm_all' => $r->norm_all ?? '',
            'tokens' => $r->tokens ?? [],
            'translit' => $r->translit ?? [],
            'token_fields' => $r->token_fields ?? [],
            'translit_fields' => $r->translit_fields ?? [],
            'strict_tokens' => $r->strict_tokens ?? [],
            'strict_translit' => $r->strict_translit ?? [],
            'title_tokens' => $r->title_tokens ?? [],
            'keyword_tokens' => $r->keyword_tokens ?? [],
            'body_tokens' => $r->body_tokens ?? [],
        ])->all();
    }

    /** Словарь поверхностных слов для исправления опечаток и подсказок. */
    public function vocabulary(string $locale): array
    {
        return SearchTerm::where('locale', $locale)
            ->orderByDesc('popularity')->orderByDesc('df')
            ->limit(8000)
            ->pluck('term')
            ->all();
    }

    protected function escapeLike(string $s): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $s);
    }

    protected static function hasStrictIndexColumns(): bool
    {
        if (self::$hasStrictIndexColumns !== null) {
            return self::$hasStrictIndexColumns;
        }

        return self::$hasStrictIndexColumns = Schema::hasColumn('search_documents', 'stem_strict')
            && Schema::hasColumn('search_documents', 'translit_strict');
    }
}
