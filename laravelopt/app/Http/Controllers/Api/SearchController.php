<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Search\SearchEngine;
use App\Search\Index\DocumentRepository;
use App\Search\Linguistics\Tokenizer;
use App\Models\SearchQueryLog;
use App\Models\SearchTerm;
use App\Models\Product;
use App\Models\Category;
use App\Models\Article;
use App\Models\Solution;
use App\Models\SolutionCategory;
use App\Models\Project;
use App\Models\Service;
use App\Models\SpecialOffer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SearchController extends Controller
{
    protected function engine(): SearchEngine
    {
        return new SearchEngine(new DocumentRepository());
    }

    protected function locale(Request $request): string
    {
        $allowed = (array) (config('frontend.locales') ?: ['ru', 'en', 'kz']);
        $locale = $request->query('locale', 'ru');
        return in_array($locale, $allowed, true) ? $locale : 'ru';
    }

    /** Полный поиск: GET /api/v1/search?q=&locale=&type=&limit= */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['query' => $q, 'total' => 0, 'results' => [], 'groups' => [], 'corrected' => null]);
        }
        $locale = $this->locale($request);

        $type = $request->query('type');
        $limit = min((int) $request->query('limit', 20), 120);

        // Основной движок работает по индексу search_documents. На боевом
        // сервере этот индекс может быть пустым/несозданным/устаревшим (после
        // переноса React -> Next), и тогда движок кидает исключение. Раньше
        // это роняло весь /v1/search в 500 — и фронт откатывался на legacy
        // /searchresult (только товары, всё помечено «по названию», 0 по всем
        // остальным типам — ровно то, что видно на скрине). Теперь ошибку
        // индекса глушим и продолжаем с databaseFallback ниже: он не зависит
        // от индекса и ищет широко — по name/brand/SKU/category/keywords и
        // раскладке клавиатуры, по ВСЕМ типам (товары, категории, решения,
        // услуги, новости, акции, проекты).
        try {
            $result = $this->engine()->search($q, [
                'locale' => $locale,
                'type' => $type,
                'limit' => $limit,
                'mode' => 'search',
            ]);
        } catch (\Throwable $e) {
            report($e);
            $result = ['query' => $q, 'corrected' => null, 'total' => 0, 'results' => [], 'groups' => []];
        }

        // Боевой fallback теперь не только спасает пустой search_documents, но
        // и дополняет индекс результатами по description/body, статьям,
        // новостям, решениям, услугам и акциям. Это важно после правок схемы:
        // до search:reindex индекс может быть частично старым, а пользователь
        // всё равно должен найти товар/контент.
        $fallback = $this->databaseFallback($q, $locale, is_string($type) ? $type : null, $limit, true);
        if ((int) ($fallback['total'] ?? 0) > 0) {
            $result = $this->mergeSearchPayloads($result, $fallback, $limit);
        }

        $this->logQuery($q, $locale, (int) ($result['total'] ?? 0), $request->ip());

        return response()->json($result);
    }

    /** Мгновенные подсказки: GET /api/v1/search/suggest?q=&locale= */
    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['query' => $q, 'suggestions' => [], 'products' => [], 'popular' => $this->popularList($this->locale($request))]);
        }

        $locale = $this->locale($request);
        $limit = min((int) $request->query('limit', 8), 12);

        try {
            $data = $this->engine()->suggest($q, [
                'locale' => $locale,
                'limit' => $limit,
            ]);
        } catch (\Throwable $e) {
            report($e);
            $data = ['query' => $q, 'corrected' => null, 'suggestions' => [], 'products' => []];
        }

        if (empty($data['suggestions']) && empty($data['products'])) {
            // 1) Сначала strict-fallback: title/SKU/brand/category/keywords.
            $fallback = $this->databaseFallback($q, $locale, null, $limit, false);
            // 2) Если strict ничего не нашёл, включаем широкий fallback по
            // description/body/статьям/новостям. Это нужно для запросов вроде
            // "гелевые" или "области", которые есть только в наполнении.
            if ((int) ($fallback['total'] ?? 0) === 0) {
                $fallback = $this->databaseFallback($q, $locale, null, $limit, true);
            }
            if ((int) ($fallback['total'] ?? 0) > 0) {
                $results = array_slice($fallback['results'], 0, $limit);
                $data = [
                    'query' => $q,
                    'corrected' => $fallback['corrected'] ?? null,
                    'suggestions' => array_map(fn ($r) => [
                        'text' => $r['title'],
                        'type' => $r['type'],
                        'url' => $r['url'],
                        'score' => $r['score'] ?? 0,
                    ], $results),
                    'products' => array_values(array_filter($results, fn ($r) => ($r['type'] ?? null) === 'product')),
                ];
            }
        }

        return response()->json($data);
    }

    /** Популярные запросы: GET /api/v1/search/popular */
    public function popular(Request $request)
    {
        return response()->json(['data' => $this->popularList($this->locale($request))]);
    }

    /** Лог клика по результату (для популярности): POST /api/v1/search/click */
    public function click(Request $request)
    {
        $data = $request->validate([
            'query' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:30',
            'id' => 'nullable|integer',
        ]);

        if (! empty($data['query'])) {
            SearchQueryLog::create([
                'query' => $data['query'],
                'normalized' => Tokenizer::normalize($data['query']),
                'locale' => $this->locale($request),
                'results_count' => 0,
                'clicked_type' => $data['type'] ?? null,
                'clicked_id' => $data['id'] ?? null,
                'ip' => $request->ip(),
                'created_at' => now(),
            ]);

            // популярность термина — для подсказок
            foreach (Tokenizer::tokenize($data['query']) as $stem) {
                SearchTerm::where('term', 'LIKE', $stem . '%')
                    ->update(['popularity' => DB::raw('popularity + 1')]);
            }
        }

        return response()->json(['ok' => true]);
    }


    /** Лог "ничего не найдено" из быстрых подсказок: POST /api/v1/search/no-results */
    public function noResults(Request $request)
    {
        $data = $request->validate([
            'query' => 'required|string|min:2|max:255',
        ]);

        $q = trim($data['query']);
        $this->logQuery($q, $this->locale($request), 0, $request->ip());

        return response()->json(['ok' => true]);
    }

    /** Агрегированный список дыр в каталоге: GET /api/v1/search/no-results?limit= */
    public function noResultsList(Request $request)
    {
        $limit = min(max((int) $request->query('limit', 30), 1), 100);
        $locale = $this->locale($request);

        $data = SearchQueryLog::where('locale', $locale)
            ->where('results_count', 0)
            ->whereNull('clicked_type')
            ->whereNull('clicked_id')
            ->whereNotNull('normalized')
            ->where('normalized', '!=', '')
            ->select('normalized', DB::raw('COUNT(*) as count'), DB::raw('MAX(created_at) as last_seen'))
            ->groupBy('normalized')
            ->orderByDesc('count')
            ->orderByDesc('last_seen')
            ->limit($limit)
            ->get();

        return response()->json(['data' => $data]);
    }


    /**
     * Безиндексный fallback для боевого сайта: ищет напрямую по коротким
     * коммерческим полям. Description/body намеренно не используются, чтобы не
     * получать выдачу вида "антенна" по запросу "акку" из-за текста питания.
     */
    protected function databaseFallback(string $q, string $locale, ?string $type, int $limit, bool $includeBody = true): array
    {
        $variants = $this->queryVariants($q);
        $items = [];
        $seen = [];
        $corrected = null;

        foreach ($variants as $variant) {
            $batch = $this->databaseFallbackForVariant($variant, $locale, $type, $limit, $includeBody);
            if (! empty($batch)) {
                if ($variant !== $q) {
                    $corrected = ['query' => $variant];
                }
                foreach ($batch as $item) {
                    $key = ($item['type'] ?? 'x') . ':' . ($item['id'] ?? '0');
                    if (! isset($seen[$key])) {
                        $items[] = $item;
                        $seen[$key] = true;
                    }
                }
                break;
            }
        }

        // groups — по ПОЛНОМУ найденному пулу (до обрезки под лимит), иначе
        // счётчики на вкладках фронта (Категории/Решения/...) обнуляются,
        // если товаров одних набралось больше лимита. results — обрезанный
        // плоский список с гарантированным местом не только товарам.
        $groups = $this->groupPresentedByType($items);
        $flat = $this->balanceFlatResults($items, $limit);

        return [
            'query' => $q,
            'corrected' => $corrected,
            'total' => count($flat),
            'results' => $flat,
            'groups' => $groups,
        ];
    }

    protected function mergeSearchPayloads(array $primary, array $fallback, int $limit): array
    {
        $items = [];
        $seen = [];

        foreach (array_merge((array) ($primary['results'] ?? []), (array) ($fallback['results'] ?? [])) as $item) {
            $key = ($item['type'] ?? 'x') . ':' . ($item['id'] ?? '0');
            if (isset($seen[$key])) {
                continue;
            }
            $items[] = $item;
            $seen[$key] = true;
        }

        // Тот же принцип, что и в databaseFallback(): groups — по полному
        // объединённому пулу (engine + fallback), иначе счётчики на
        // вкладках фронта обнуляются, если после объединения товаров стало
        // больше лимита. results — сбалансированный плоский список.
        $groups = $this->groupPresentedByType($items);
        $flat = $this->balanceFlatResults($items, $limit);

        return [
            'query' => $primary['query'] ?? $fallback['query'] ?? '',
            'corrected' => $primary['corrected'] ?? $fallback['corrected'] ?? null,
            'total' => count($flat),
            'results' => $flat,
            'groups' => $groups,
        ];
    }

    protected function databaseFallbackForVariant(string $variant, string $locale, ?string $type, int $limit, bool $includeBody = true): array
    {
        $results = [];
        $perTypeLimit = max(8, min(60, $limit));

        if ($type === null || $type === 'product') {
            $products = Product::with(['brand', 'category', 'translate'])
                ->where('status', 0)
                ->where(function ($query) use ($variant, $locale, $includeBody) {
                    $this->applyProductSearchWhere($query, $variant, $locale, $includeBody);
                })
                ->limit($perTypeLimit)
                ->get();

            foreach ($products as $product) {
                $results[] = $this->presentProductFallback($product, $variant, $locale);
            }
        }

        if ($type === null || $type === 'category') {
            $categories = Category::where('status', 0)
                ->where(function ($query) use ($variant, $includeBody) {
                    $columns = ['name', 'name_ru', 'name_en', 'name_kz', 'meta_title', 'meta_title_ru', 'meta_title_en', 'meta_title_kz', 'meta_keywords', 'meta_keywords_ru', 'meta_keywords_en', 'meta_keywords_kz', 'slug'];
                    if ($includeBody) {
                        $columns = array_merge($columns, ['short_description', 'short_description_en', 'short_description_kz', 'description', 'description_en', 'description_kz', 'meta_description', 'meta_description_en', 'meta_description_kz', 'seo_text_top', 'seo_text_top_en', 'seo_text_top_kz', 'seo_text_bottom', 'seo_text_bottom_en', 'seo_text_bottom_kz']);
                    }
                    $this->applySimpleSearchWhere($query, $variant, $columns);
                })
                ->limit(30)
                ->get();

            foreach ($categories as $category) {
                $title = $this->localizedValue($category, 'name', $locale) ?: $category->slug;
                $body = $includeBody ? $this->fallbackText($category, ['short_description', 'description', 'meta_description', 'seo_text_top', 'seo_text_bottom'], $locale) : '';
                $keywords = $this->fallbackText($category, ['meta_keywords'], $locale);
                $results[] = $this->presentGenericFallback($category->id, 'category', $title, $this->frontUrl('category', $category->slug, $locale), $category->image ?? null, $variant, 1.35, $keywords, $body);
            }
        }

        if ($type === null || $type === 'article') {
            $articles = Article::where('status', 0)
                ->where(function ($query) use ($variant, $includeBody) {
                    $columns = ['title', 'title_ru', 'title_en', 'title_kz', 'meta_title', 'meta_title_ru', 'meta_title_en', 'meta_title_kz', 'meta_keywords', 'meta_keywords_ru', 'meta_keywords_en', 'meta_keywords_kz', 'slug'];
                    if ($includeBody) {
                        $columns = array_merge($columns, ['excerpt', 'excerpt_en', 'excerpt_kz', 'description', 'description_ru', 'description_en', 'description_kz', 'meta_description', 'meta_description_en', 'meta_description_kz', 'seo_h1', 'seo_h1_en', 'seo_h1_kz', 'seo_h2']);
                    }
                    $this->applySimpleSearchWhere($query, $variant, $columns);
                })
                ->limit(20)
                ->get();

            foreach ($articles as $article) {
                $title = $this->localizedValue($article, 'title', $locale) ?: $article->slug;
                $keywords = trim($this->fallbackText($article, ['meta_keywords'], $locale) . ' ' . (is_array($article->tags) ? implode(' ', $article->tags) : ''));
                $body = $includeBody ? $this->fallbackText($article, ['excerpt', 'description', 'meta_description', 'seo_h1', 'seo_h2'], $locale) : '';
                $results[] = $this->presentGenericFallback($article->id, 'article', $title, $this->frontUrl('article', $article->slug, $locale), $article->image ?? null, $variant, 0.58, $keywords, $body);
            }
        }

        if ($type === null || $type === 'solution') {
            $solutions = Solution::where('status', 0)
                ->where(function ($query) use ($variant, $includeBody) {
                    $columns = ['title', 'title_ru', 'title_en', 'title_kz', 'meta_title', 'meta_title_ru', 'meta_title_en', 'meta_title_kz', 'meta_keywords', 'meta_keywords_ru', 'meta_keywords_en', 'meta_keywords_kz', 'slug'];
                    if ($includeBody) {
                        $columns = array_merge($columns, ['description', 'description_ru', 'description_en', 'description_kz', 'meta_description', 'meta_description_en', 'meta_description_kz', 'seo_h1', 'seo_h1_en', 'seo_h1_kz', 'seo_h2']);
                    }
                    $this->applySimpleSearchWhere($query, $variant, $columns);
                })
                ->limit(20)
                ->get();

            foreach ($solutions as $solution) {
                $title = $this->localizedValue($solution, 'title', $locale) ?: $solution->slug;
                $keywords = $this->fallbackText($solution, ['meta_keywords'], $locale);
                $body = $includeBody ? $this->fallbackText($solution, ['description', 'meta_description', 'seo_h1', 'seo_h2'], $locale) : '';
                $results[] = $this->presentGenericFallback($solution->id, 'solution', $title, $this->frontUrl('solution', $solution->slug, $locale), $solution->image ?? null, $variant, 0.9, $keywords, $body);
            }
        }

        if ($type === null || $type === 'solcategory') {
            $solcategories = SolutionCategory::where('status', 0)
                ->where(function ($query) use ($variant, $includeBody) {
                    $columns = ['title', 'title_ru', 'title_en', 'title_kz', 'meta_title', 'meta_title_ru', 'meta_title_en', 'meta_title_kz', 'meta_keywords', 'meta_keywords_ru', 'meta_keywords_en', 'meta_keywords_kz', 'slug'];
                    if ($includeBody) {
                        $columns = array_merge($columns, ['description', 'description_ru', 'description_en', 'description_kz', 'meta_description', 'meta_description_en', 'meta_description_kz', 'seo_text_top', 'seo_text_top_en', 'seo_text_top_kz', 'seo_text_bottom', 'seo_text_bottom_en', 'seo_text_bottom_kz', 'seo_h1', 'seo_h1_en', 'seo_h1_kz', 'seo_h2']);
                    }
                    $this->applySimpleSearchWhere($query, $variant, $columns);
                })
                ->limit(20)
                ->get();

            foreach ($solcategories as $category) {
                $title = $this->localizedValue($category, 'title', $locale) ?: $category->slug;
                $keywords = $this->fallbackText($category, ['meta_keywords'], $locale);
                $body = $includeBody ? $this->fallbackText($category, ['description', 'meta_description', 'seo_text_top', 'seo_text_bottom', 'seo_h1', 'seo_h2'], $locale) : '';
                $results[] = $this->presentGenericFallback($category->id, 'solcategory', $title, $this->frontUrl('solcategory', $category->slug, $locale), $category->image ?? null, $variant, 1.15, $keywords, $body);
            }
        }

        if ($type === null || $type === 'project') {
            $projects = Project::where('status', 0)
                ->where(function ($query) use ($variant, $includeBody) {
                    $columns = ['title', 'title_ru', 'title_en', 'title_kz', 'client', 'location', 'slug'];
                    if ($includeBody) {
                        $columns = array_merge($columns, ['description', 'description_ru', 'description_en', 'description_kz', 'meta_description', 'meta_description_en', 'meta_description_kz', 'seo_h1', 'seo_h1_en', 'seo_h1_kz', 'seo_h2']);
                    }
                    $this->applySimpleSearchWhere($query, $variant, $columns);
                })
                ->limit(20)
                ->get();

            foreach ($projects as $project) {
                $title = $this->localizedValue($project, 'title', $locale) ?: $project->slug;
                $keywords = trim(($project->client ?? '') . ' ' . ($project->location ?? ''));
                $body = $includeBody ? $this->fallbackText($project, ['description', 'meta_description', 'seo_h1', 'seo_h2'], $locale) : '';
                $results[] = $this->presentGenericFallback($project->id, 'project', $title, $this->frontUrl('project', $project->slug, $locale), $project->image ?? null, $variant, 0.48, $keywords, $body);
            }
        }

        if ($type === null || $type === 'service') {
            $services = Service::where('status', 0)
                ->where(function ($query) use ($variant, $includeBody) {
                    $columns = ['title', 'title_ru', 'title_en', 'title_kz', 'meta_title', 'meta_title_ru', 'meta_title_en', 'meta_title_kz', 'meta_keywords', 'meta_keywords_ru', 'meta_keywords_en', 'meta_keywords_kz', 'slug'];
                    if ($includeBody) {
                        $columns = array_merge($columns, ['description', 'description_ru', 'description_en', 'description_kz', 'meta_description', 'meta_description_en', 'meta_description_kz']);
                    }
                    $this->applySimpleSearchWhere($query, $variant, $columns);
                })
                ->limit(20)
                ->get();

            foreach ($services as $service) {
                $title = $this->localizedValue($service, 'title', $locale) ?: $service->slug;
                $keywords = $this->fallbackText($service, ['meta_keywords'], $locale);
                $body = $includeBody ? $this->fallbackText($service, ['description', 'meta_description'], $locale) : '';
                $results[] = $this->presentGenericFallback($service->id, 'service', $title, $this->frontUrl('service', $service->slug, $locale), $service->image ?? null, $variant, 0.82, $keywords, $body);
            }
        }

        if ($type === null || $type === 'offer') {
            $offers = SpecialOffer::where('status', 0)
                ->where(function ($query) use ($variant, $includeBody) {
                    $columns = ['title', 'title_ru', 'title_en', 'title_kz', 'meta_title', 'meta_title_ru', 'meta_title_en', 'meta_title_kz', 'meta_keywords', 'meta_keywords_ru', 'meta_keywords_en', 'meta_keywords_kz', 'slug'];
                    if ($includeBody) {
                        $columns = array_merge($columns, ['description', 'description_ru', 'description_en', 'description_kz', 'meta_description', 'meta_description_en', 'meta_description_kz']);
                    }
                    $this->applySimpleSearchWhere($query, $variant, $columns);
                })
                ->limit(20)
                ->get();

            foreach ($offers as $offer) {
                $title = $this->localizedValue($offer, 'title', $locale) ?: $offer->slug;
                $keywords = $this->fallbackText($offer, ['meta_keywords'], $locale);
                $body = $includeBody ? $this->fallbackText($offer, ['description', 'meta_description'], $locale) : '';
                $results[] = $this->presentGenericFallback($offer->id, 'offer', $title, $this->frontUrl('offer', $offer->slug, $locale), $offer->image ?? null, $variant, 0.72, $keywords, $body);
            }
        }

        usort($results, fn ($a, $b) => $this->comparePresentedResults($a, $b));
        return array_slice($results, 0, $limit);
    }

    protected function applyProductSearchWhere($query, string $variant, string $locale, bool $includeBody = true): void
    {
        $columns = ['name', 'meta_title', 'meta_keywords', 'SKU', 'slug'];
        if ($includeBody) {
            $columns = array_merge($columns, ['short_description', 'description', 'meta_description', 'char', 'usage']);
        }
        $this->applySimpleSearchWhere($query, $variant, $columns);

        if ($locale !== 'ru') {
            $suffix = $locale === 'kz' ? 'kz' : 'en';
            $query->orWhereHas('translate', function ($translate) use ($variant, $suffix, $includeBody) {
                $columns = ['name_' . $suffix, 'meta_title_' . $suffix, 'meta_keywords_' . $suffix];
                if ($includeBody) {
                    $columns = array_merge($columns, ['short_description_' . $suffix, 'description_' . $suffix, 'meta_description_' . $suffix, 'char_' . $suffix, 'usage_' . $suffix]);
                }
                $this->applySimpleSearchWhere($translate, $variant, $columns);
            });
        }

        $query->orWhereHas('brand', function ($brand) use ($variant) {
            $this->applySimpleSearchWhere($brand, $variant, ['name', 'slug']);
        });

        $query->orWhereHas('category', function ($category) use ($variant) {
            $this->applySimpleSearchWhere($category, $variant, ['name', 'name_ru', 'name_en', 'name_kz', 'meta_title', 'meta_title_ru', 'meta_title_en', 'meta_title_kz', 'meta_keywords', 'meta_keywords_ru', 'meta_keywords_en', 'meta_keywords_kz', 'slug']);
        });
    }

    protected function applySimpleSearchWhere($query, string $variant, array $columns): void
    {
        $table = method_exists($query, 'getModel') ? $query->getModel()->getTable() : null;
        if ($table) {
            $columns = array_values(array_filter($columns, fn ($column) => Schema::hasColumn($table, $column)));
        }

        if (empty($columns)) {
            return;
        }

        $tokens = array_values(array_unique(array_filter(preg_split('/\s+/u', Tokenizer::normalize($variant), -1, PREG_SPLIT_NO_EMPTY))));
        $needles = array_values(array_unique(array_filter(array_merge([$variant], $tokens))));

        foreach ($columns as $column) {
            foreach ($needles as $needle) {
                $query->orWhere($column, 'LIKE', '%' . $this->escapeLike($needle) . '%');
            }
        }
    }

    protected function presentProductFallback(Product $product, string $variant, string $locale): array
    {
        $title = $this->localizedProductValue($product, 'name', $locale) ?: $product->name ?: $product->slug;
        $keywords = trim(implode(' ', array_filter([
            $this->localizedProductValue($product, 'meta_keywords', $locale),
            $product->SKU,
            optional($product->brand)->name,
            optional($product->category)->name,
            $this->localizedValue($product->category, 'name', $locale),
        ])));
        $body = trim(implode(' ', array_filter([
            $this->localizedProductValue($product, 'short_description', $locale),
            $this->localizedProductValue($product, 'description', $locale),
            $this->localizedProductValue($product, 'meta_description', $locale),
            $locale === 'ru' ? ($product->char ?? null) : null,
            $locale === 'ru' ? ($product->usage ?? null) : null,
        ])));

        $scoreData = $this->fallbackScore($title, $keywords, $body, $variant, 1.75);

        return [
            'id' => $product->id,
            'type' => 'product',
            'title' => $title,
            'url' => $this->frontUrl('product', $product->slug, $locale),
            'image' => $this->imageUrl($product->image ?: $product->images, 'products'),
            'price' => $product->price_on_request ? null : $product->price,
            'currency' => $product->currency,
            'score' => $scoreData['score'],
            'match' => [
                'body_only' => false,
                'requires_body' => false,
                'matched_fields' => $scoreData['fields'],
                'manual_boost' => 0,
                'how' => ['fallback' => true],
            ],
        ];
    }

    protected function presentGenericFallback($id, string $type, ?string $title, ?string $url, ?string $image, string $variant, float $weight, string $keywords = '', string $body = ''): array
    {
        $scoreData = $this->fallbackScore((string) $title, $keywords, $body, $variant, $weight);

        return [
            'id' => $id,
            'type' => $type,
            'title' => $title,
            'url' => $url,
            'image' => $this->imageUrl($image, $this->imageFolderForType($type)),
            'price' => null,
            'currency' => null,
            'score' => $scoreData['score'],
            'match' => [
                'body_only' => false,
                'requires_body' => false,
                'matched_fields' => $scoreData['fields'],
                'manual_boost' => 0,
                'how' => ['fallback' => true],
            ],
        ];
    }

    protected function fallbackScore(string $title, string $keywords, string $body, string $variant, float $typeWeight): array
    {
        $q = Tokenizer::normalize($variant);
        $titleNorm = Tokenizer::normalize($title);
        $keywordsNorm = Tokenizer::normalize($keywords);
        $bodyNorm = Tokenizer::normalize($this->stripHtml($body));
        $score = 0.0;
        $fields = [];

        if ($q !== '' && $titleNorm === $q) {
            $score += 120;
            $fields[] = 'title';
        } elseif ($q !== '' && str_starts_with($titleNorm, $q)) {
            $score += 95;
            $fields[] = 'title';
        } elseif ($q !== '' && str_contains($titleNorm, $q)) {
            $score += 82;
            $fields[] = 'title';
        }

        $tokens = array_values(array_filter(preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY)));
        $matchedTitleTokens = 0;
        foreach ($tokens as $token) {
            if (mb_strlen($token, 'UTF-8') < 2) {
                continue;
            }
            if (str_contains($titleNorm, $token)) {
                $matchedTitleTokens++;
            } elseif (str_contains($keywordsNorm, $token)) {
                $score += 14;
                $fields[] = 'keywords';
            } elseif (str_contains($bodyNorm, $token)) {
                $score += 4;
                $fields[] = 'body';
            }
        }

        if ($matchedTitleTokens > 0) {
            $score += 42 * ($matchedTitleTokens / max(1, count($tokens)));
            $fields[] = 'title';
        }

        if ($score <= 0 && $q !== '' && str_contains($keywordsNorm, $q)) {
            $score = 38;
            $fields[] = 'keywords';
        }

        if ($score <= 0 && $q !== '' && str_contains($bodyNorm, $q)) {
            $score = 12;
            $fields[] = 'body';
        }

        if (! empty($fields) && count(array_unique($fields)) === 1 && in_array('body', $fields, true)) {
            $score *= 0.72;
        }

        $fields = array_values(array_unique($fields ?: ['keywords']));
        return ['score' => round(max(1, $score) * $typeWeight, 3), 'fields' => $fields];
    }

    /**
     * Плоский список результатов (для вкладки «Все») — товары идут первыми
     * (так и просили), НО не занимают 100% мест. Для короткого частого
     * слова вроде "test" товаров (с учётом нечёткого/триграм-совпадения)
     * легко набирается больше, чем весь $limit — тогда при жёсткой
     * сортировке «товар всегда выше не-товара» категории/решения/etc
     * физически не помещаются в обрезанный список, хотя реально найдены
     * (что и приводило к нулям на вкладках Категории/Решения на фронте —
     * счётчики там считаются именно по этому списку).
     * Резервируем товарам не больше 70% лимита, остаток — остальным типам
     * по релевантности, но с гарантированной квотой на тип (см.
     * pickWithPerTypeQuota) — иначе тот же баг просто переезжает на уровень
     * ниже: один объёмный не-товарный тип (например, 101 совпадение по
     * категориям) выедает весь остаток, и малочисленный, но реально
     * найденный тип (например, 4 решения) полностью вылетает из плоского
     * списка, хотя счётчик на вкладке «Решения» продолжает показывать 4
     * (groups считается по полному пулу до обрезки) — пользователь видит
     * ненулевую цифру на вкладке и пустой список после клика.
     */
    protected function balanceFlatResults(array $items, int $limit): array
    {
        usort($items, fn ($a, $b) => $this->comparePresentedResults($a, $b));

        $products = array_values(array_filter($items, fn ($i) => (string) ($i['type'] ?? '') === 'product'));
        $others = array_values(array_filter($items, fn ($i) => (string) ($i['type'] ?? '') !== 'product'));

        if (empty($others)) {
            return array_slice($products, 0, $limit);
        }
        if (empty($products)) {
            return array_slice($others, 0, $limit);
        }

        $productBudget = (int) floor($limit * 0.7);
        $selectedProducts = array_slice($products, 0, $productBudget);
        $remaining = max(0, $limit - count($selectedProducts));
        $selectedOthers = $this->pickWithPerTypeQuota($others, $remaining);

        $result = array_merge($selectedProducts, $selectedOthers);
        usort($result, fn ($a, $b) => $this->comparePresentedResults($a, $b));

        return $result;
    }

    /**
     * Забирает до $budget элементов из $items (уже отсортированных по
     * релевантности через comparePresentedResults), не давая одному
     * объёмному типу выесть весь бюджет целиком. Сначала у каждого
     * встретившегося типа резервируем небольшую гарантированную квоту
     * (не больше 6 мест и не больше того, что у него реально есть),
     * а всё, что осталось после этого — раздаём по убыванию score без
     * оглядки на тип. Так тип с всего 2-4 реальными совпадениями не
     * теряется целиком за спиной типа с сотней совпадений, и счётчик
     * на вкладке фронта всегда соответствует тому, что реально попадёт
     * в список при клике по этой вкладке.
     */
    protected function pickWithPerTypeQuota(array $items, int $budget): array
    {
        if ($budget <= 0) {
            return [];
        }

        $byType = [];
        foreach ($items as $item) {
            $byType[(string) ($item['type'] ?? '')][] = $item;
        }

        $typeCount = count($byType);
        if ($typeCount === 0) {
            return [];
        }

        $minPerType = max(1, (int) floor($budget / ($typeCount * 2)));
        $minPerType = min($minPerType, 6);

        $selected = [];
        $selectedKeys = [];
        foreach ($byType as $type => $group) {
            foreach (array_slice($group, 0, $minPerType) as $item) {
                $key = $type . ':' . ($item['id'] ?? '');
                $selected[] = $item;
                $selectedKeys[$key] = true;
            }
        }

        if (count($selected) >= $budget) {
            usort($selected, fn ($a, $b) => $this->comparePresentedResults($a, $b));
            return array_slice($selected, 0, $budget);
        }

        $rest = array_values(array_filter($items, function ($item) use ($selectedKeys) {
            $key = ((string) ($item['type'] ?? '')) . ':' . ($item['id'] ?? '');
            return ! isset($selectedKeys[$key]);
        }));

        $needed = $budget - count($selected);
        $selected = array_merge($selected, array_slice($rest, 0, $needed));

        usort($selected, fn ($a, $b) => $this->comparePresentedResults($a, $b));

        return $selected;
    }

    protected function comparePresentedResults(array $a, array $b): int
    {
        // Заказчик просил: первыми в выдаче всегда товары, потом всё
        // остальное. Поэтому товар всегда выше не-товара, независимо от
        // score. Внутри же одной группы (товар vs товар, или прочее vs
        // прочее) сортируем по релевантности, как и раньше.
        $aProduct = (string) ($a['type'] ?? '') === 'product' ? 1 : 0;
        $bProduct = (string) ($b['type'] ?? '') === 'product' ? 1 : 0;
        if ($aProduct !== $bProduct) {
            return $bProduct <=> $aProduct;
        }

        $scoreA = (float) ($a['score'] ?? 0);
        $scoreB = (float) ($b['score'] ?? 0);
        $priority = (array) config('search.type_priority', []);
        $priorityA = (int) ($priority[(string) ($a['type'] ?? '')] ?? 0);
        $priorityB = (int) ($priority[(string) ($b['type'] ?? '')] ?? 0);

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

    protected function fallbackText($model, array $bases, string $locale): string
    {
        $parts = [];
        foreach ($bases as $base) {
            $value = $this->localizedValue($model, $base, $locale);
            if ($value !== null && $value !== '') {
                $parts[] = $value;
            }
        }

        return trim(implode(' ', $parts));
    }

    protected function stripHtml(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $value = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $value) ?? $value;
        $value = preg_replace('#<[^>]+>#', ' ', $value) ?? $value;
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace("\xC2\xA0", ' ', $value);

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    protected function queryVariants(string $q): array
    {
        $variants = [];
        $add = static function (string $value) use (&$variants) {
            $value = trim($value);
            if ($value !== '' && ! in_array($value, $variants, true)) {
                $variants[] = $value;
            }
        };

        $add($q);
        if (preg_match('/[a-z`\[\];\',.]/i', $q) && ! preg_match('/\p{Cyrillic}/u', $q)) {
            $add($this->fixKeyboardLayoutLatToCyr($q));
        }

        return $variants;
    }

    protected function fixKeyboardLayoutLatToCyr(string $value): string
    {
        $map = [
            'q' => 'й', 'w' => 'ц', 'e' => 'у', 'r' => 'к', 't' => 'е', 'y' => 'н', 'u' => 'г', 'i' => 'ш', 'o' => 'щ', 'p' => 'з', '[' => 'х', ']' => 'ъ',
            'a' => 'ф', 's' => 'ы', 'd' => 'в', 'f' => 'а', 'g' => 'п', 'h' => 'р', 'j' => 'о', 'k' => 'л', 'l' => 'д', ';' => 'ж', "'" => 'э',
            'z' => 'я', 'x' => 'ч', 'c' => 'с', 'v' => 'м', 'b' => 'и', 'n' => 'т', 'm' => 'ь', ',' => 'б', '.' => 'ю', '`' => 'ё',
        ];

        $value = mb_strtolower($value, 'UTF-8');
        $out = '';
        $length = mb_strlen($value, 'UTF-8');
        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($value, $i, 1, 'UTF-8');
            $out .= $map[$char] ?? $char;
        }

        return trim(preg_replace('/\s+/u', ' ', $out));
    }

    protected function localizedProductValue(Product $product, string $base, string $locale): ?string
    {
        if ($locale !== 'ru' && $product->relationLoaded('translate') && $product->translate) {
            $suffix = $locale === 'kz' ? 'kz' : 'en';
            $value = $product->translate->{$base . '_' . $suffix} ?? null;
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $product->{$base} ?? null;
    }

    protected function localizedValue($model, string $base, string $locale): ?string
    {
        if (! $model) {
            return null;
        }

        $attrs = method_exists($model, 'getAttributes') ? $model->getAttributes() : [];
        $order = $locale === 'ru'
            ? [$base, $base . '_ru']
            : [$base . '_' . ($locale === 'kz' ? 'kz' : 'en'), $base, $base . '_ru'];

        foreach ($order as $column) {
            if (array_key_exists($column, $attrs) && $attrs[$column] !== null && $attrs[$column] !== '') {
                return (string) $attrs[$column];
            }
        }

        return null;
    }

    protected function frontUrl(string $type, ?string $slug, string $locale): ?string
    {
        if (! $slug) {
            return null;
        }

        if ($type === 'category') return '/category/' . $slug;
        if ($type === 'solcategory') return '/solutions/' . $slug;
        return '/' . $type . '/' . $slug;
    }

    protected function imageUrl(?string $file, string $folder): ?string
    {
        if (! $file) return null;
        if (preg_match('#^https?://#i', $file) || str_starts_with($file, '/')) return $file;
        return rtrim(config('app.url'), '/') . '/storage/' . trim($folder, '/') . '/' . ltrim($file, '/');
    }

    protected function imageFolderForType(string $type): string
    {
        return [
            'category' => 'categories',
            'solcategory' => 'solcategories',
            'solution' => 'solutions',
            'article' => 'articles',
            'project' => 'projects',
            'service' => 'services',
            'offer' => 'offers',
            'product' => 'products',
        ][$type] ?? ($type . 's');
    }

    protected function groupPresentedByType(array $items): array
    {
        $groups = [];
        foreach ($items as $item) {
            $groups[$item['type']][] = $item;
        }
        return $groups;
    }

    protected function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    protected function popularList(string $locale): array
    {
        return SearchQueryLog::where('locale', $locale)
            ->where(function ($q) {
                $q->where('results_count', '>', 0)->orWhereNotNull('clicked_type');
            })
            ->whereNotNull('normalized')->where('normalized', '!=', '')
            ->select('normalized', DB::raw('COUNT(*) as c'))
            ->groupBy('normalized')->orderByDesc('c')->limit(8)
            ->pluck('normalized')->all();
    }

    protected function logQuery(string $q, string $locale, int $count, ?string $ip): void
    {
        SearchQueryLog::create([
            'query' => $q,
            'normalized' => Tokenizer::normalize($q),
            'locale' => $locale,
            'results_count' => $count,
            'ip' => $ip,
            'created_at' => now(),
        ]);
    }
}
