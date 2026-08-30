<?php

namespace App\Search\Index;

use Illuminate\Support\Facades\DB;
use App\Models\SearchDocument;
use App\Models\SearchTerm;
use App\Search\Document;
use App\Search\Linguistics\Tokenizer;
use App\Search\Linguistics\Transliterator;

use App\Models\Product;
use App\Models\Category;
use App\Models\Article;
use App\Models\Solution;
use App\Models\SolutionCategory;
use App\Models\Project;
use App\Models\Service;
use App\Models\SpecialOffer;

/**
 * Строит поисковый индекс (search_documents + search_terms) из всех сущностей
 * по каждой локали. Запускается командой search:reindex и из observers.
 */
class DocumentIndexer
{
    protected array $locales;

    public function __construct()
    {
        $this->locales = (array) (config('frontend.locales') ?: config('search.locales', ['ru', 'en', 'kz']));
    }

    /** Полная переиндексация. Возвращает кол-во документов. */
    public function reindexAll(?callable $progress = null): int
    {
        SearchTerm::query()->delete();

        $count = 0;
        foreach ($this->sources() as $type => [$class, $extractor]) {
            $class::query()->chunk(200, function ($rows) use (&$count, $type, $extractor, $progress) {
                foreach ($rows as $row) {
                    $count += $this->indexModel($type, $row, $extractor);
                    if ($progress) $progress($type, $count);
                }
            });
        }

        $this->pruneOrphans();

        return $count;
    }

    /** Переиндексировать одну запись (для observers). */
    public function indexOne(string $type, $model): void
    {
        $extractor = $this->sources()[$type][1] ?? null;
        if ($extractor) {
            $this->indexModel($type, $model, $extractor);
        }
    }

    public function removeFor(string $modelClass, $id): void
    {
        SearchDocument::where('searchable_type', $modelClass)
            ->where('searchable_id', $id)->delete();
    }

    /* --------------------------------------------------------------------- */

    protected function indexModel(string $type, $model, callable $extractor): int
    {
        $written = 0;
        $previous = app()->getLocale();

        foreach ($this->locales as $locale) {
            app()->setLocale($locale);
            $data = $extractor($model, $locale);
            if (empty($data['title'])) {
                continue;
            }

            $built = Document::build($data['title'], $data['keywords'] ?? '', $data['body'] ?? '');

            SearchDocument::updateOrCreate(
                [
                    'searchable_type' => get_class($model),
                    'searchable_id' => $model->id,
                    'locale' => $locale,
                ],
                array_merge($built, [
                    'type' => $type,
                    'title' => $data['title'],
                    'url' => $data['url'] ?? null,
                    'image' => $data['image'] ?? null,
                    'price' => $data['price'] ?? null,
                    'currency' => $data['currency'] ?? null,
                    'in_stock' => $data['in_stock'] ?? false,
                    'popularity' => $data['popularity'] ?? 0,
                    'weight' => config('search.type_weights')[$type] ?? 1.0,
                ])
            );

            $this->indexTerms($data['title'] . ' ' . ($data['keywords'] ?? ''), $locale);
            $written++;
        }

        app()->setLocale($previous);

        return $written;
    }

    /** Накопление словаря терминов (поверхностные слова заголовков/ключевиков). */
    protected function indexTerms(string $text, string $locale): void
    {
        $words = preg_split('/[\s\-]+/u', Tokenizer::normalize($text), -1, PREG_SPLIT_NO_EMPTY);
        foreach (array_unique($words) as $w) {
            if (mb_strlen($w, 'UTF-8') < 3) continue;
            SearchTerm::query()->updateOrInsert(
                ['term' => $w, 'locale' => $locale],
                ['translit' => Transliterator::toLatin($w), 'df' => DB::raw('df + 1'), 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    protected function pruneOrphans(): void
    {
        foreach ($this->sources() as $type => [$class]) {
            $ids = $class::query()->pluck('id');
            SearchDocument::where('searchable_type', $class)
                ->whereNotIn('searchable_id', $ids)->delete();
        }
    }

    /* ---------------- Реестр сущностей и извлечение полей ---------------- */

    protected function sources(): array
    {
        return [
            'category' => [Category::class, fn ($m, $l) => [
                'title' => $this->inline($m, 'name', $l),
                'keywords' => trim(($this->inline($m, 'meta_keywords', $l) ?? '')),
                'body' => trim(implode(' ', array_filter([
                    $this->inline($m, 'short_description', $l),
                    $this->inline($m, 'description', $l),
                    $this->inline($m, 'meta_description', $l),
                    $this->inline($m, 'seo_text_top', $l),
                    $this->inline($m, 'seo_text_bottom', $l),
                ]))),
                'url' => $this->url('category', $m->slug, $l),
                'image' => $this->img($m->image, 'categories'),
                'popularity' => 0.6,
                'in_stock' => true,
            ]],
            'product' => [Product::class, fn ($m, $l) => [
                'title' => $this->productField($m, 'name', $l),
                'keywords' => trim(implode(' ', array_filter([
                    $this->productField($m, 'meta_keywords', $l),
                    $m->SKU, optional($m->brand)->name, optional($m->category)->name,
                ]))),
                'body' => trim(implode(' ', array_filter([
                    $this->productField($m, 'short_description', $l),
                    $this->productField($m, 'description', $l),
                    $this->productField($m, 'meta_description', $l),
                    $m->char,
                    $m->usage,
                ]))),
                'url' => $this->url('product', $m->slug, $l),
                'image' => $this->img($m->image, 'products'),
                'price' => $m->price_on_request ? null : $m->price,
                'currency' => $m->currency,
                'in_stock' => ((int) ($m->status ?? 0) === 0) && ! $m->price_on_request && ($m->availability ?? 'InStock') === 'InStock',
                'popularity' => $this->productPopularity($m),
            ]],
            'article' => [Article::class, fn ($m, $l) => [
                'title' => $this->inline($m, 'title', $l),
                'keywords' => trim((is_array($m->tags) ? implode(' ', $m->tags) : '') . ' ' . ($this->inline($m, 'meta_keywords', $l) ?? '')),
                'body' => trim(implode(' ', array_filter([
                    $this->inline($m, 'excerpt', $l),
                    $this->inline($m, 'description', $l),
                    $this->inline($m, 'meta_description', $l),
                    $this->inline($m, 'seo_h1', $l),
                    $m->seo_h2 ?? null,
                ]))),
                'url' => $this->url('article', $m->slug, $l),
                'image' => $this->img($m->image, 'articles'),
                'popularity' => 0.35,
                'in_stock' => true,
            ]],
            'solution' => [Solution::class, fn ($m, $l) => [
                'title' => $this->inline($m, 'title', $l),
                'keywords' => $this->inline($m, 'meta_keywords', $l) ?? '',
                'body' => trim(implode(' ', array_filter([
                    $this->inline($m, 'description', $l),
                    $this->inline($m, 'meta_description', $l),
                    $this->inline($m, 'seo_h1', $l),
                    $m->seo_h2 ?? null,
                ]))),
                'url' => $this->url('solution', $m->slug, $l),
                'image' => $this->img($m->image, 'solutions'),
                'popularity' => 0.5,
                'in_stock' => true,
            ]],
            'solcategory' => [SolutionCategory::class, fn ($m, $l) => [
                'title' => $this->inline($m, 'title', $l),
                'keywords' => $this->inline($m, 'meta_keywords', $l) ?? '',
                'body' => trim(implode(' ', array_filter([
                    $this->inline($m, 'description', $l),
                    $this->inline($m, 'meta_description', $l),
                    $this->inline($m, 'seo_text_top', $l),
                    $this->inline($m, 'seo_text_bottom', $l),
                    $this->inline($m, 'seo_h1', $l),
                    $m->seo_h2 ?? null,
                ]))),
                'url' => $this->url('solcategory', $m->slug, $l),
                'image' => $this->img($m->image, 'solcategories'),
                'popularity' => 0.55,
                'in_stock' => true,
            ]],
            'project' => [Project::class, fn ($m, $l) => [
                'title' => $this->inline($m, 'title', $l),
                'keywords' => trim(($m->client ?? '') . ' ' . ($m->location ?? '')),
                'body' => trim(implode(' ', array_filter([
                    $this->inline($m, 'description', $l),
                    $this->inline($m, 'meta_description', $l),
                    $this->inline($m, 'seo_h1', $l),
                    $m->seo_h2 ?? null,
                    is_array($m->result_metrics) ? implode(' ', $m->result_metrics) : null,
                ]))),
                'url' => $this->url('project', $m->slug, $l),
                'image' => $this->img($m->image, 'projects'),
                'popularity' => 0.3,
                'in_stock' => true,
            ]],
            'service' => [Service::class, fn ($m, $l) => [
                'title' => $this->inline($m, 'title', $l),
                'keywords' => $this->inline($m, 'meta_keywords', $l) ?? '',
                'body' => trim(implode(' ', array_filter([
                    $this->inline($m, 'description', $l),
                    $this->inline($m, 'meta_description', $l),
                ]))),
                'url' => $this->url('service', $m->slug, $l),
                'image' => $this->img($m->image, 'services'),
                'popularity' => 0.35,
                'in_stock' => true,
            ]],
            'offer' => [SpecialOffer::class, fn ($m, $l) => [
                'title' => $this->inline($m, 'title', $l),
                'keywords' => $this->inline($m, 'meta_keywords', $l) ?? '',
                'body' => trim(implode(' ', array_filter([
                    $this->inline($m, 'description', $l),
                    $this->inline($m, 'meta_description', $l),
                ]))),
                'url' => $this->url('offer', $m->slug, $l),
                'image' => $this->img($m->image, 'offers'),
                'popularity' => 0.35,
                'in_stock' => true,
            ]],
        ];
    }

    /** Локализация inline-поля (_en/_kz), фолбэк на RU/базовое. */
    protected function inline($m, string $base, string $l): ?string
    {
        $ru = $m->{$base . '_ru'} ?? $m->{$base};
        if ($l === 'ru') return $ru;
        $val = $m->{$base . ($l === 'kz' ? '_kz' : '_en')} ?? null;
        return ($val !== null && $val !== '') ? $val : $ru;
    }

    /** Локализация поля товара (EN/KZ из product_translates). */
    protected function productField($m, string $base, string $l): ?string
    {
        $ru = $m->{$base . '_ru'} ?? $m->{$base};
        if ($l === 'ru') return $ru;
        $t = $m->translate;
        $val = $t ? ($t->{$base . ($l === 'kz' ? '_kz' : '_en')} ?? null) : null;
        return ($val !== null && $val !== '') ? $val : $ru;
    }

    protected function productPopularity($m): float
    {
        $r = (float) ($m->rating ?? 0) / 5.0;
        $rev = min(1.0, (int) ($m->reviews_count ?? 0) / 50.0);
        return round(min(1.0, 0.2 + 0.5 * $r + 0.3 * $rev), 3);
    }

    protected function url(string $type, ?string $slug, string $locale): ?string
    {
        if (class_exists(\App\Support\FrontendUrls::class)) {
            return \App\Support\FrontendUrls::page($type, $slug, $locale);
        }
        $prefix = $locale === ($this->locales[0] ?? 'ru') ? '' : '/' . $locale;
        return $prefix . '/' . $type . '/' . $slug;
    }

    protected function img(?string $file, string $folder): ?string
    {
        if (class_exists(\App\Support\FrontendUrls::class)) {
            return \App\Support\FrontendUrls::storage($file, $folder);
        }
        return $file ? rtrim(config('app.url'), '/') . '/storage/' . $folder . '/' . $file : null;
    }
}
