<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Project;
use App\Models\License;
use App\Models\Partner;
use App\Models\Article;
use App\Models\Service;
use App\Models\Slider;
use App\Models\Solution;
use App\Models\Setting;
use App\Models\SolutionCategory;
use App\Models\SpecialOffer;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use App\Models\ProductTranslate;
use App\Models\SearchQueryLog;
use App\Search\Linguistics\Tokenizer;
use Illuminate\Database\Eloquent\Model;

class FrontendController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ЛОКАЛИЗАЦИЯ / SEO
    |--------------------------------------------------------------------------
    | Стратегия (по договорённости): endpoint'ы возвращают ПОЛНЫЕ модели —
    | все колонки, все языки и все SEO-поля отдаются сырыми, фронт сам выбирает
    | нужный язык. Дополнительно проставляем "удобные" локализованные ключи
    | (name/title/description/meta и seo-поля и т.д.) для обратной совместимости с
    | текущим Next.js: если фронт читает `title`, он его получит; при этом
    | title_ru/_en/_kz также присутствуют в ответе.
    |
    | Заодно устранён старый баг ветвления `elseif ($locale === 'ru-Ru' || 'ru')`
    | (условие всегда истинно) — язык нормализуется в одном месте, см. localeOf().
    */

    /** Базовые текстовые ключи, для которых создаём локализованный алиас. */
    private const LOCALIZE_KEYS = [
        'name', 'title', 'description', 'short_description', 'excerpt',
        'meta_title', 'meta_description', 'meta_keywords',
        'seo_h1', 'seo_h2', 'seo_text_top', 'seo_text_bottom',
        'og_title', 'og_description',
        'image_alt', 'image_title', 'breadcrumb_title',
    ];

    /** Ключи перевода товара (лежат в product_translates c суффиксом _en/_kz). */
    private const PRODUCT_TRANSLATE_KEYS = [
        'name', 'description', 'usage', 'char', 'short_description',
        'meta_title', 'meta_description', 'meta_keywords', 'seo_h1',
    ];

    /** Нормализуем Accept-Language -> 'ru' | 'en' | 'kz'. */
    private function localeOf(Request $request): string
    {
        $l = strtolower(trim((string) $request->header('Accept-Language')));
        if (str_starts_with($l, 'en')) return 'en';
        if (str_starts_with($l, 'kz') || str_starts_with($l, 'kk')) return 'kz';
        return 'ru';
    }


    /** EN-раскладка, которой пользователь хотел набрать русский запрос. */
    private function fixKeyboardLayoutLatToCyr(string $value): string
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

    /** Варианты запроса: оригинал + исправленная EN->RU раскладка. */
    private function searchWordVariants(string $word): array
    {
        $variants = [];
        $add = static function (string $value) use (&$variants) {
            $value = trim($value);
            if ($value !== '' && ! in_array($value, $variants, true)) {
                $variants[] = $value;
            }
        };

        $add($word);

        if (preg_match('/[a-z]/i', $word) && ! preg_match('/\p{Cyrillic}/u', $word)) {
            $add($this->fixKeyboardLayoutLatToCyr($word));
        }

        return $variants;
    }

    /**
     * Значение базового ключа на нужном языке из набора атрибутов.
     * Порядок поиска: <key>_<locale> -> <key> (bare = ru) -> <key>_ru.
     */
    private function pickFrom(array $attrs, string $key, string $locale)
    {
        $order = $locale === 'ru'
            ? [$key, $key . '_ru']
            : [$key . '_' . $locale, $key, $key . '_ru'];

        foreach ($order as $col) {
            if (array_key_exists($col, $attrs) && $attrs[$col] !== null && $attrs[$col] !== '') {
                return $attrs[$col];
            }
        }
        return null;
    }

    /** Проставляет удобные локализованные ключи в модель (сырые поля остаются). */
    private function localizeModel(?Model $model, string $locale, array $keys = self::LOCALIZE_KEYS): ?Model
    {
        if (! $model) return $model;

        $attrs = $model->getAttributes();
        foreach ($keys as $key) {
            $val = $this->pickFrom($attrs, $key, $locale);
            if ($val !== null) {
                $model->setAttribute($key, $val);
            }
        }
        return $model;
    }

    /** То же для коллекции (+ рекурсивно для загруженных children). */
    private function localizeCollection($items, string $locale, array $keys = self::LOCALIZE_KEYS)
    {
        if (! $items) return $items;
        foreach ($items as $item) {
            $this->localizeModel($item, $locale, $keys);
            if ($item instanceof Model && $item->relationLoaded('children')) {
                $this->localizeCollection($item->children, $locale, $keys);
            }
        }
        return $items;
    }

    /**
     * Локализация товара: базовые ru-поля лежат в products, переводы (_en/_kz) —
     * в product_translates. Мержим их в удобные ключи, сырые остаются.
     * Отношение translate подгружается и уходит во фронт целиком.
     */
    private function localizeProduct(?Product $product, string $locale): ?Product
    {
        if (! $product) return $product;

        // сначала общие SEO-ключи с самого товара (og_*, image_alt, canonical и т.д.)
        $this->localizeModel($product, $locale);

        $translate = $product->relationLoaded('translate') ? $product->translate : $product->translate()->first();
        $tAttrs = $translate ? $translate->getAttributes() : [];
        $pAttrs = $product->getAttributes();

        foreach (self::PRODUCT_TRANSLATE_KEYS as $key) {
            if ($locale === 'ru') {
                // ru: значение с товара (или *_ru, если так называется колонка)
                $val = $this->pickFrom($pAttrs, $key, 'ru');
            } else {
                // en/kz: перевод из product_translates, иначе ru-значение с товара
                $val = $this->pickFrom($tAttrs, $key, $locale);
                if ($val === null) {
                    $val = $this->pickFrom($pAttrs, $key, 'ru');
                }
            }
            if ($val !== null) {
                $product->setAttribute($key, $val);
            }
        }

        return $product;
    }

    // =========================================================================
    // CATEGORIES
    // =========================================================================

    public function getAllCategories(Request $request)
    {
        $locale = $this->localeOf($request);

        $categories = Category::whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->where('status', 0)->orderBy('sort_order', 'ASC');
            }])
            ->where('status', 0)
            ->orderBy('sort_order', 'ASC')
            ->get();

        $this->localizeCollection($categories, $locale);

        return response()->json([
            'status' => 200,
            'categories' => $categories,
        ]);
    }

    /**
     * Лёгкая выборка для sitemap.xml — ВСЕ категории (все 3 уровня, не
     * только корень + прямые дети, как в getAllCategories), с полным путём
     * слагов (path) для сборки вложенного URL /catalog/a/b/c, и SEO-полями
     * in_sitemap/is_indexable/sitemap_priority/sitemap_changefreq — раньше
     * фронтовый sitemap.ts их вообще не учитывал (генерировал только корневые
     * категории со статичным приоритетом/частотой).
     */
    public function getCategoriesForSitemap()
    {
        $categories = Category::where('status', 0)
            ->orderBy('sort_order', 'ASC')
            ->get(['id', 'slug', 'parent_id', 'updated_at', 'in_sitemap', 'is_indexable', 'sitemap_priority', 'sitemap_changefreq']);

        $byId = $categories->keyBy('id');

        $result = $categories->map(function ($c) use ($byId) {
            $path = [$c->slug];
            $p = $c->parent_id ? $byId->get($c->parent_id) : null;
            while ($p) {
                array_unshift($path, $p->slug);
                $p = $p->parent_id ? $byId->get($p->parent_id) : null;
            }

            return [
                'path' => $path,
                'updated_at' => $c->updated_at,
                'in_sitemap' => (bool) $c->in_sitemap,
                'is_indexable' => (bool) $c->is_indexable,
                'sitemap_priority' => $c->sitemap_priority,
                'sitemap_changefreq' => $c->sitemap_changefreq,
            ];
        })->values();

        return response()->json(['status' => 200, 'categories' => $result]);
    }

    /**
     * Лёгкая выборка товаров для sitemap.xml — раньше товары в sitemap не
     * попадали вообще ни одним товаром.
     */
    public function getProductsForSitemap()
    {
        $products = Product::where('status', 0)
            ->get(['slug', 'updated_at', 'in_sitemap', 'is_indexable', 'sitemap_priority', 'sitemap_changefreq']);

        return response()->json(['status' => 200, 'products' => $products]);
    }

    /**
     * Лёгкая выборка решений для sitemap.xml, с slug'ом категории решения
     * (нужен для сборки вложенного URL /solutions/{cat}/{solution}).
     */
    public function getSolutionsForSitemap()
    {
        $solutions = Solution::where('status', 0)
            ->with(['category:id,slug'])
            ->get(['id', 'slug', 'category_id', 'updated_at', 'in_sitemap', 'is_indexable', 'sitemap_priority', 'sitemap_changefreq']);

        $result = $solutions->map(fn ($s) => [
            'slug' => $s->slug,
            'category_slug' => $s->category->slug ?? null,
            'updated_at' => $s->updated_at,
            'in_sitemap' => (bool) $s->in_sitemap,
            'is_indexable' => (bool) $s->is_indexable,
            'sitemap_priority' => $s->sitemap_priority,
            'sitemap_changefreq' => $s->sitemap_changefreq,
        ])->filter(fn ($s) => $s['category_slug'])->values();

        return response()->json(['status' => 200, 'solutions' => $result]);
    }

    public function getCategory($category_slug, Request $request)
    {
        $locale = $this->localeOf($request);

        $category = Category::where('slug', $category_slug)
            ->with(['children' => function ($query) {
                $query->where('status', 0)->orderBy('sort_order', 'ASC');
            }])
            ->where('status', 0)
            ->first();

        $this->localizeModel($category, $locale);
        if ($category && $category->relationLoaded('children')) {
            $this->localizeCollection($category->children, $locale);
        }

        return response()->json([
            'status' => 200,
            'category' => $category,
        ]);
    }

    public function getSubSubCategory($category_slug, $subcategory_slug, $subsubcategory_slug, Request $request)
    {
        $locale = $this->localeOf($request);

        $pcategory      = Category::where('slug', $category_slug)->where('status', 0)->first();
        $subcategory    = Category::where('slug', $subcategory_slug)->where('status', 0)->first();
        $subsubcategory = Category::where('slug', $subsubcategory_slug)->where('status', 0)->first();

        $this->localizeModel($pcategory, $locale);
        $this->localizeModel($subcategory, $locale);
        $this->localizeModel($subsubcategory, $locale);

        return response()->json([
            'status' => 200,
            'subcategory' => $subcategory,
            'pcategory' => $pcategory,
            'subsubcategory' => $subsubcategory,
        ]);
    }

    public function getSubCategory($category_slug, $subcategory_slug, Request $request)
    {
        $locale = $this->localeOf($request);

        $pcategory = Category::where('slug', $category_slug)->where('status', 0)->first();

        $subcategory = Category::where('slug', $subcategory_slug)
            ->with(['children' => function ($query) {
                $query->where('status', 0)->orderBy('sort_order', 'ASC');
            }])
            ->where('status', 0)
            ->first();

        $this->localizeModel($pcategory, $locale);
        $this->localizeModel($subcategory, $locale);
        if ($subcategory && $subcategory->relationLoaded('children')) {
            $this->localizeCollection($subcategory->children, $locale);
        }

        return response()->json([
            'status' => 200,
            'subcategory' => $subcategory,
            'pcategory' => $pcategory,
        ]);
    }

    public function getProductsByCategory($category_slug, Request $request)
    {
        $category = Category::where('slug', $category_slug)->where('status', 0)->first();
        if (! $category) {
            return response()->json(['status' => 404, 'products' => []]);
        }

        // Порядок на странице категории:
        // 1) ручной «Порядок сортировки» из админки (sort_order > 0) —
        //    всегда первый, по возрастанию (1, 2, 3...), независимо от режима;
        // 2) дальше — выбранный режим сортировки: дата (по умолчанию —
        //    новые сверху, ?sort=date_asc — старые сверху) или алфавит
        //    (?sort=name_asc — А-Я, ?sort=name_desc — Я-А).
        $sort = $request->query('sort');

        $query = Product::where('category_id', $category->id)
            ->where('status', 0)
            ->orderByRaw('CASE WHEN sort_order > 0 THEN 0 ELSE 1 END ASC')
            // Ранг ВНУТРИ группы "запинено" — только для sort_order > 0.
            // Раньше здесь стоял голый orderBy('sort_order','ASC') без CASE,
            // и он безусловно шёл ПЕРЕД алфавитом/датой для АБСОЛЮТНО всех
            // товаров, а не только для запиненных. Если у товаров вне ручного
            // пина sort_order всё равно был не строго 0 у всех одинаково
            // (например, после массового переноса), то name/created_at ниже
            // до сравнения вообще не доходили — сортировка выглядела
            // полностью нерабочей. CASE здесь искусственно уравнивает всех
            // НЕ запиненных в 0, так что для них дальше решает уже выбранный
            // режим (алфавит/дата), а не их сырой sort_order.
            ->orderByRaw('CASE WHEN sort_order > 0 THEN sort_order ELSE 0 END ASC');

        if ($sort === 'name_asc') {
            $query->orderBy('name', 'ASC');
        } elseif ($sort === 'name_desc') {
            $query->orderBy('name', 'DESC');
        } else {
            // Товары, попавшие в базу не через админку (массовый перенос/
            // импорт), могли остаться без created_at — миграция products
            // создана обычным $table->timestamps() без useCurrent(), значит
            // для таких строк created_at = NULL.
            $direction = $sort === 'date_asc' ? 'ASC' : 'DESC';
            $query->orderBy('created_at', $direction);
        }

        $products = $query->get();

        return response()->json([
            'status' => 200,
            'products' => $products,
        ]);
    }

    // =========================================================================
    // PRODUCTS
    // =========================================================================

    public function getProduct($product_slug, Request $request)
    {
        $locale = $this->localeOf($request);

        $product = Product::where('slug', $product_slug)
            ->where('status', 0)
            ->with('translate')
            ->first();

        $this->localizeProduct($product, $locale);

        $brand = $product ? Brand::where('id', $product->brand_id)->first() : null;

        $related = collect();
        if ($product) {
            // Раньше тут всегда была случайная выборка по категории, а поле
            // related_products (уже выбирается в админке) нигде не читалось.
            // Сначала пробуем то, что реально выбрал админ, порядок —
            // как выбрано в списке; если ничего не выбрано — старое
            // поведение (случайные товары той же категории) как фолбэк.
            $relatedIds = array_filter((array) ($product->related_products ?? []));
            if (! empty($relatedIds)) {
                $curated = Product::whereIn('id', $relatedIds)
                    ->where('status', 0)
                    ->with('translate')
                    ->get()
                    ->sortBy(fn ($p) => array_search($p->id, $relatedIds))
                    ->values();
                $related = $curated;
            }

            if ($related->isEmpty()) {
                $related = Product::where('category_id', $product->category_id)
                    ->where('id', '!=', $product->id)
                    ->where('status', 0)
                    ->with('translate')
                    ->inRandomOrder()
                    ->take(4)
                    ->get();
            }

            foreach ($related as $r) {
                $this->localizeProduct($r, $locale);
            }
        }

        return response()->json([
            'status' => 200,
            'product' => $product,
            'brand' => $brand,
            'related' => $related,
        ]);
    }

    // =========================================================================
    // BRANDS
    // =========================================================================

    public function getBrands(Request $request)
    {
        $brands = Brand::where('status', '0')->orderBy('sort_order', 'ASC')->orderBy('id', 'DESC')->get();

        // Бренды — единственная сущность, которая раньше отдавалась вообще без
        // локализации: SEO-поля у неё появились только сейчас, вместе с
        // переводами meta_*/seo_h1/alt.
        $this->localizeCollection($brands, $this->localeOf($request));

        return response()->json([
            'status' => 200,
            'brands' => $brands,
        ]);
    }

    public function getOneBrand($slug, Request $request)
    {
        $brand = Brand::where('slug', $slug)->where('status', 0)->first();
        if (! $brand) {
            return response()->json(['status' => 404, 'brand' => []]);
        }

        $this->localizeModel($brand, $this->localeOf($request));

        // ВАЖНО: фильтр по status. Раньше здесь его не было, поэтому на
        // странице бренда показывались ВСЕ товары марки, включая скрытые
        // в админке (status != 0). Скрытые товары не должны быть видны на
        // сайте — как и в getProductsByCategory, берём только status = 0.
        $oneBrand = Product::where('brand_id', $brand->id)
            ->where('status', 0)
            ->orderBy('id', 'DESC')
            ->get();

        $dataArray = [];
        foreach ($oneBrand as $value) {
            $dataArray[] = [
                "id" => $value['id'],
                "name" => $value['name'],
                "slug" => $value['slug'],
                "image" => $value['image'],
                "SKU" => $value['SKU'],
            ];
        }

        return response()->json([
            'status' => 200,
            'brand' => $dataArray,
            // Плоские ключи остаются ради обратной совместимости со старым
            // фронтом; brand_info — целиком локализованная модель со всем
            // SEO-набором (canonical, robots, og_*, seo_text_* и т.д.).
            'brand_info' => $brand,
            'brand_name' => $brand->name,
            'brand_description' => $brand->meta_description,
            'brand_keywords' => $brand->meta_keywords,
            'brand_title' => $brand->meta_title,
            'brand_image' => $brand->image,
        ]);
    }

    public function getProductsForSearch($searchWord, Request $request)
    {
        $searchWord = trim(urldecode((string) $searchWord));

        if (mb_strlen($searchWord) < 2) {
            return response()->json([
                'status' => 404,
                'message' => 'По вашему запросу ничего не найдено',
            ]);
        }

        $variants = $this->searchWordVariants($searchWord);

        // Legacy fallback для React-старого endpoint тоже должен быть строгим:
        // ищем только по коммерческим полям шапки (name, SKU, meta_title,
        // meta_keywords, brand, category), но не по description/meta_description.
        $products = Product::with(['brand', 'category'])
            ->where('status', 0)
            ->where(function ($query) use ($variants) {
                foreach ($variants as $variant) {
                    $like = '%' . $variant . '%';
                    $query->orWhere('name', 'LIKE', $like)
                        ->orWhere('meta_title', 'LIKE', $like)
                        ->orWhere('meta_keywords', 'LIKE', $like)
                        ->orWhere('SKU', 'LIKE', $like)
                        ->orWhereHas('brand', function ($brand) use ($like) {
                            $brand->where('name', 'LIKE', $like);
                        })
                        ->orWhereHas('category', function ($category) use ($like) {
                            $category->where('name', 'LIKE', $like)
                                ->orWhere('meta_title', 'LIKE', $like)
                                ->orWhere('meta_keywords', 'LIKE', $like);
                        });
                }
            })
            ->orderBy('id', 'DESC')
            ->limit(50)
            ->get();

        if ($products->isEmpty()) {
            SearchQueryLog::create([
                'query' => $searchWord,
                'normalized' => Tokenizer::normalize($searchWord),
                'locale' => $this->localeOf($request),
                'results_count' => 0,
                'ip' => $request->ip(),
                'created_at' => now(),
            ]);

            return response()->json([
                'status' => 404,
                'message' => 'По вашему запросу ничего не найдено',
            ]);
        }

        $dataArray = [];
        foreach ($products as $value) {
            $dataArray[] = [
                "id" => $value['id'],
                "name" => $value['name'],
                "images" => $value['image'] ?: $value['images'],
                "slug" => $value['slug'],
            ];
        }

        return response()->json([
            'status' => 200,
            'products' => $dataArray,
        ]);
    }

    public function getCategories()
    {
        $category = Category::where('status', '0')->get();

        return response()->json([
            'status' => 200,
            'category' => $category,
        ]);
    }

    // =========================================================================
    // BITRIX24 lead forms (логика без изменений)
    // =========================================================================

    public function setOrder(Request $request)
    {
        $userName = $request->name;
        $userPhone = $request->phone;
        $userEmail = $request->email;
        $userComment = $request->comment;
        $arPhone = (!empty($userPhone)) ? array(array('VALUE' => $userPhone, 'VALUE_TYPE' => 'MOBILE')) : array();
        $arEmail = (!empty($userEmail)) ? array(array('VALUE' => $userEmail, 'VALUE_TYPE' => 'WORK')) : array();

        $client = new Client([
            'headers' => ['content-type' => 'application/json', 'Accept' => 'application/json'],
        ]);

        $sendNewRequest = $client->request(
            'POST',
            'https://optech.bitrix24.kz/rest/8/vsy8i4vvg06m4b6k/crm.lead.add.json',
            [
                'form_params' => [
                    'fields' => [
                        "TITLE" => "Заявка с сайта Optech.kz",
                        "NAME" => $userName,
                        "PHONE" => $arPhone,
                        "EMAIL" => $arEmail,
                        "COMMENTS" => $userComment,
                    ]
                ]
            ]
        );

        return response()->json([
            'status' => 200,
        ]);
    }

    public function setPrice(Request $request)
    {
        $userName = $request->name;
        $userPhone = $request->phone;
        $userEmail = $request->email;
        $userComment = $request->comment;
        $userProduct = $request->product;
        $arPhone = (!empty($userPhone)) ? array(array('VALUE' => $userPhone, 'VALUE_TYPE' => 'MOBILE')) : array();
        $arEmail = (!empty($userEmail)) ? array(array('VALUE' => $userEmail, 'VALUE_TYPE' => 'WORK')) : array();

        $client = new Client([
            'headers' => ['content-type' => 'application/json', 'Accept' => 'application/json'],
        ]);

        $sendNewRequest = $client->request(
            'POST',
            'https://optech.bitrix24.kz/rest/8/b1udpid2xex48o5y/crm.lead.add.json',
            [
                'form_params' => [
                    'fields' => [
                        "TITLE" => "Запрос цены товара с сайта Optech.kz",
                        "NAME" => $userName,
                        "PHONE" => $arPhone,
                        "UF_CRM_1717496565" => $userProduct,
                        "EMAIL" => $arEmail,
                        "COMMENTS" => $userComment,
                    ]
                ]
            ]
        );

        return response()->json([
            'status' => 200,
        ]);
    }

    // =========================================================================
    // PROJECTS
    // =========================================================================

    public function getProjects(Request $request)
    {
        $locale = $this->localeOf($request);

        $projects = Project::where('status', 0)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'DESC')
            ->get();

        $this->localizeCollection($projects, $locale);

        return response()->json([
            'status' => 200,
            'projects' => $projects,
        ]);
    }

    public function getProject($project_slug, Request $request)
    {
        $locale = $this->localeOf($request);

        $project = Project::where('slug', $project_slug)->where('status', 0)->first();
        $this->localizeModel($project, $locale);

        return response()->json([
            'status' => 200,
            'project' => $project,
        ]);
    }

    // =========================================================================
    // ARTICLES
    // =========================================================================

    public function getArticles(Request $request)
    {
        // Новости и материалы Базы знаний лежат в одной таблице; без фильтра
        // по типу раздел «Новости» показывал бы и SEO-статьи тоже.
        return $this->articlesOfType(Article::TYPE_ARTICLE, $request);
    }

    public function getArticle($article_slug, Request $request)
    {
        return $this->articleOfType(Article::TYPE_ARTICLE, $article_slug, $request);
    }

    // =========================================================================
    // KNOWLEDGE BASE (/basa-znani)
    // =========================================================================

    public function getKnowledgeArticles(Request $request)
    {
        return $this->articlesOfType(Article::TYPE_KNOWLEDGE, $request);
    }

    public function getKnowledgeArticle($article_slug, Request $request)
    {
        return $this->articleOfType(Article::TYPE_KNOWLEDGE, $article_slug, $request);
    }

    /**
     * Список материалов одного раздела.
     *
     * Материалы, созданные до появления колонки type, лежат с NULL — считаем
     * их новостями, иначе после миграции раздел «Новости» опустел бы.
     */
    private function articlesOfType(string $type, Request $request)
    {
        $locale = $this->localeOf($request);

        $articles = Article::where('status', 0)
            ->where(function ($q) use ($type) {
                $q->where('type', $type);
                if ($type === Article::TYPE_ARTICLE) {
                    $q->orWhereNull('type');
                }
            })
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->get();

        $this->localizeCollection($articles, $locale);

        return response()->json([
            'status' => 200,
            'articles' => $articles,
        ]);
    }

    private function articleOfType(string $type, string $slug, Request $request)
    {
        $locale = $this->localeOf($request);

        $article = Article::where('slug', $slug)
            ->where('status', 0)
            ->where(function ($q) use ($type) {
                $q->where('type', $type);
                if ($type === Article::TYPE_ARTICLE) {
                    $q->orWhereNull('type');
                }
            })
            ->first();

        if (! $article) {
            return response()->json(['status' => 404, 'article' => null], 404);
        }

        $this->localizeModel($article, $locale);

        return response()->json([
            'status' => 200,
            'article' => $article,
        ]);
    }

    // =========================================================================
    // SERVICES
    // =========================================================================

    public function getServices(Request $request)
    {
        $locale = $this->localeOf($request);

        $services = Service::where('status', 0)->get();
        $this->localizeCollection($services, $locale);

        return response()->json([
            'status' => 200,
            'services' => $services,
        ]);
    }

    public function getService($service_slug, Request $request)
    {
        $locale = $this->localeOf($request);

        $service = Service::where('slug', $service_slug)->where('status', 0)->first();
        $this->localizeModel($service, $locale);

        return response()->json([
            'status' => 200,
            'service' => $service,
        ]);
    }

    // =========================================================================
    // SPECIAL OFFERS
    // =========================================================================

    public function getOffers(Request $request)
    {
        $locale = $this->localeOf($request);

        $offers = SpecialOffer::where('status', 0)->get();
        $this->localizeCollection($offers, $locale);

        return response()->json([
            'status' => 200,
            'offers' => $offers,
        ]);
    }

    public function getOffer($offer_slug, Request $request)
    {
        $locale = $this->localeOf($request);

        $offer = SpecialOffer::where('slug', $offer_slug)->where('status', 0)->first();
        $this->localizeModel($offer, $locale);

        return response()->json([
            'status' => 200,
            'offer' => $offer,
        ]);
    }

    // =========================================================================
    // SOLUTION CATEGORIES / SOLUTIONS
    // =========================================================================

    public function getSolCategories(Request $request)
    {
        $locale = $this->localeOf($request);

        $solcategories = SolutionCategory::where('status', 0)
            ->orderBy('sort_order', 'ASC')
            ->get();

        $this->localizeCollection($solcategories, $locale);

        return response()->json([
            'status' => 200,
            'solcategories' => $solcategories,
        ]);
    }

    public function getSolCategory($solcategory_slug, Request $request)
    {
        $locale = $this->localeOf($request);

        $solcategory = SolutionCategory::where('slug', $solcategory_slug)->first();

        $solutions = Solution::where('category_id', $solcategory->id ?? 0)
            ->where('status', 0)
            ->orderBy('sort_order', 'ASC')
            ->get();

        $this->localizeModel($solcategory, $locale);
        $this->localizeCollection($solutions, $locale);

        return response()->json([
            'status' => 200,
            'solutions' => $solutions,
            // строка (как в исходном контракте), но берётся уже локализованный title
            'solcategory' => $solcategory ? $solcategory->title : null,
            // сама модель категории со всеми SEO-полями (seo_text_top/bottom, faq, og_* ...)
            'solcategory_data' => $solcategory,
        ]);
    }

    public function getSolution($solution_category_slug, $solution_slug, Request $request)
    {
        $locale = $this->localeOf($request);

        $solcategory = SolutionCategory::where('slug', $solution_category_slug)
            ->where('status', 0)
            ->first();

        $solution = Solution::where('slug', $solution_slug)
            ->where('status', 0)
            ->first();

        $this->localizeModel($solcategory, $locale);
        $this->localizeModel($solution, $locale);

        return response()->json([
            'status' => 200,
            'solution' => $solution,
            'solcategory' => $solcategory ? $solcategory->title : null,
            'solcategory_data' => $solcategory,
        ]);
    }

    // =========================================================================
    // LICENSES / PARTNERS / SLIDER / SETTINGS
    // (возвращают полные модели — все новые поля уже отдаются сырыми)
    // =========================================================================

    public function getLicenses(Request $request)
    {
        $licenses = \Illuminate\Support\Facades\Schema::hasColumn('licenses', 'position')
            ? License::orderBy('position', 'ASC')->orderBy('id', 'DESC')->get()
            : License::all();

        return response()->json([
            'status' => 200,
            'licenses' => $licenses,
        ]);
    }

    public function getPartners(Request $request)
    {
        $partners = Partner::orderBy('position', 'ASC')->get();

        return response()->json([
            'status' => 200,
            'partners' => $partners,
        ]);
    }

    public function getSlider(Request $request)
    {
        $sliders = Slider::orderBy('position', 'ASC')->get();

        return response()->json([
            'status' => 200,
            'sliders' => $sliders,
        ]);
    }

    public function getSettings(Request $request)
    {
        $settings = Setting::orderBy('id', 'ASC')->first();

        return response()->json([
            'status' => 200,
            'settings' => $settings,
        ]);
    }
}
