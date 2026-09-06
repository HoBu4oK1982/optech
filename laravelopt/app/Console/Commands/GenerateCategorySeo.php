<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\AnthropicClient;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Генерация SEO-текстов категорий каталога через Anthropic Messages API.
 *
 * Приоритеты взяты из выгрузки SE Ranking по Google.kz (апрель 2026):
 * категории, которые уже близко к топу, требуют другого текста, чем те,
 * которых в индексе нет вообще. Поэтому промпт для каждой группы свой.
 */
class GenerateCategorySeo extends Command
{
    protected $signature = 'optech:seo:generate
        {--category= : slug одной категории — обработать только её}
        {--priority= : 1, 2 или 3 — только категории этого приоритета}
        {--limit= : максимум категорий за запуск}
        {--force : перезаписывать SEO-текст, даже если он уже заполнен}
        {--dry-run : ничего не сохранять, только показать, что получилось}';

    protected $description = 'Сгенерировать meta-теги и SEO-тексты категорий через Claude';

    /** Приоритет 1 — позиции 4-10 в Google.kz, задача вытянуть в топ-3. */
    private const PRIORITY_1 = [
        'Анализаторы спектра' => 6,
        'Оптические рефлектометры' => 6,
        'Оптические анализаторы спектра' => 6,
        'Сварочные аппараты для сварки оптоволокна' => 4,
        'Прокладка оптического кабеля' => 5,
        'Скалыватели оптического волокна' => 8,
        'Инструменты для монтажа оптических кабелей' => 4,
        'Безэховые камеры для тестирования РЭА' => 3,
        'Аналоговые мультиметры' => 3,
        'Безбумажные регистраторы' => 7,
        'Системы автоматизированного тестирования РЭА' => 7,
        'Позиционеры и преобразователи' => 8,
    ];

    /** Приоритет 2 — позиции 10-30, нужен расширенный текст. */
    private const PRIORITY_2 = [
        'Векторные анализаторы цепей' => 10,
        'Мультиметр измеритель LCR' => 10,
        'Анализаторы сигналов' => 20,
        'Измерители оптической мощности' => 20,
        'Оптические сплиттеры PLC' => 13,
        'Оптические адаптеры' => 9,
        'Калибраторы' => 9,
        'Инструмент для разделки оптического кабеля' => 9,
        'Тестеры Open RAN' => 13,
        'Кроссы оптические' => 18,
        'Аналитические системы' => 15,
        'Вихревые расходометры' => 18,
        'Датчики давления для нефтеперерабатывающей' => 13,
    ];

    /** Приоритет 3 — вне индекса, нужен максимально детальный текст. */
    private const PRIORITY_3 = [
        'Генераторы сигналов',
        'Измерители мощности',
        'Осциллографы',
        'Токоизмерительные клещи',
        'Электроизмерительное оборудование',
        'Газовые анализаторы',
        'Источники оптического излучения',
        'Оптические аттенюаторы',
        'Оптические кабели',
        'Оптические патч-корды',
        'Компрессоры',
        'Аккумуляторные батареи',
        'ИБП',
        'Инженерные системы',
    ];

    private LoggerInterface $log;
    private AnthropicClient $claude;

    /** @var array<string,int> суммарный расход токенов за запуск */
    private array $usage = ['input_tokens' => 0, 'output_tokens' => 0];

    public function handle(): int
    {
        $this->log = Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/seo-generation.log'),
            'level' => 'debug',
        ]);

        try {
            $this->claude = AnthropicClient::fromConfig(fn (string $m) => $this->log->info($m));
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $categories = $this->selectCategories();

        if ($categories->isEmpty()) {
            $this->warn('Под заданные условия не подошла ни одна категория.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->info(sprintf(
            'Категорий к обработке: %d | модель: %s%s',
            $categories->count(),
            $this->claude->model(),
            $dryRun ? ' | РЕЖИМ ПРОСМОТРА, ничего не сохраняется' : ''
        ));
        $this->log->info(str_repeat('=', 70));
        $this->log->info(sprintf('Запуск: категорий %d, dry-run: %s', $categories->count(), $dryRun ? 'да' : 'нет'));

        $bar = $this->output->createProgressBar($categories->count());
        $bar->setFormat(" %current%/%max% [%bar%] %percent:3s%%\n %message%");
        $bar->setMessage('старт');
        $bar->start();

        $updated = $failed = 0;
        $results = [];

        foreach ($categories as $category) {
            $bar->setMessage(mb_strimwidth($category->name, 0, 60, '…'));

            try {
                $seo = $this->generateFor($category);
                $applied = $this->apply($category, $seo, $dryRun);
                $updated++;
                $results[] = [$category->slug, $this->tierLabel($category), implode(', ', $applied) ?: '—'];
            } catch (Throwable $e) {
                $failed++;
                $this->log->error("[{$category->slug}] {$e->getMessage()}");
                $results[] = [$category->slug, $this->tierLabel($category), 'ОШИБКА: ' . mb_strimwidth($e->getMessage(), 0, 60, '…')];
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['Категория', 'Приоритет', 'Обновлено'], $results);
        $this->summary($updated, $failed, $dryRun);

        return $failed > 0 && $updated === 0 ? self::FAILURE : self::SUCCESS;
    }

    // -----------------------------------------------------------------------
    // Отбор категорий
    // -----------------------------------------------------------------------

    private function selectCategories(): Collection
    {
        $query = Category::where('status', 0);

        if ($slug = $this->option('category')) {
            $query->where('slug', $slug);
        }

        $categories = $query->orderBy('sort_order')->get();

        if ($priority = $this->option('priority')) {
            $categories = $categories->filter(fn ($c) => (string) $this->tierOf($c) === (string) $priority);
        }

        // Без --force не трогаем то, где SEO-текст уже написан: команда
        // рассчитана на повторные запуски и не должна затирать ручную работу.
        if (! $this->option('force')) {
            $categories = $categories->filter(fn ($c) => blank($c->seo_text_top));
        }

        if ($limit = (int) $this->option('limit')) {
            $categories = $categories->take($limit);
        }

        return $categories->values();
    }

    /** Нормализация названия для сопоставления с выгрузкой SE Ranking. */
    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace('ё', 'е', $value);

        return preg_replace('/\s+/u', ' ', $value);
    }

    /** 1, 2, 3 или null, если категории нет в выгрузке. */
    private function tierOf(Category $category): ?int
    {
        $name = $this->normalize((string) $category->name);

        foreach ([1 => self::PRIORITY_1, 2 => self::PRIORITY_2] as $tier => $list) {
            foreach ($list as $listName => $position) {
                if ($this->matches($name, $listName)) {
                    return $tier;
                }
            }
        }

        foreach (self::PRIORITY_3 as $listName) {
            if ($this->matches($name, $listName)) {
                return 3;
            }
        }

        return null;
    }

    private function positionOf(Category $category): ?int
    {
        $name = $this->normalize((string) $category->name);

        foreach ([self::PRIORITY_1, self::PRIORITY_2] as $list) {
            foreach ($list as $listName => $position) {
                if ($this->matches($name, $listName)) {
                    return $position;
                }
            }
        }

        return null;
    }

    /**
     * Названия в выгрузке SE Ranking не всегда совпадают с названиями в базе
     * дословно: «Датчики давления для нефтеперерабатывающей» — обрезанная
     * фраза, «ИБП» лежит в базе как «Источники бесперебойного питания (ИБП)».
     * Поэтому кроме точного равенства принимаем вхождение фразы из выгрузки
     * в название категории — но только целыми словами.
     *
     * Обратное направление (название категории внутри фразы из выгрузки)
     * намеренно НЕ проверяется: на нём «Бумажные регистраторы» подхватывали
     * позицию от «Безбумажные регистраторы» — это разные категории.
     */
    private function matches(string $normalizedName, string $listName): bool
    {
        $listName = $this->normalize($listName);

        if ($normalizedName === $listName) {
            return true;
        }

        return (bool) preg_match(
            '/(?<![\p{L}\p{N}])' . preg_quote($listName, '/') . '(?![\p{L}\p{N}])/u',
            $normalizedName
        );
    }

    private function tierLabel(Category $category): string
    {
        $tier = $this->tierOf($category);
        $position = $this->positionOf($category);

        if ($tier === null) {
            return 'вне выгрузки';
        }

        return $position ? "П{$tier} (позиция {$position})" : "П{$tier}";
    }

    // -----------------------------------------------------------------------
    // Контекст категории
    // -----------------------------------------------------------------------

    /** @return array<string,mixed> */
    private function contextFor(Category $category): array
    {
        // Цепочка родителей: текст для подкатегории должен знать, частью чего
        // она является, иначе получается набор оторванных друг от друга страниц.
        $chain = [];
        $node = $category;
        $guard = 0;
        while ($node && $guard++ < 10) {
            array_unshift($chain, $node->name);
            $node = $node->parent_id ? Category::find($node->parent_id) : null;
        }

        $children = Category::where('parent_id', $category->id)
            ->where('status', 0)
            ->pluck('name')
            ->take(20)
            ->all();

        $branchIds = $this->branchIds($category);

        $products = Product::whereIn('category_id', $branchIds)->where('status', 0);
        $productCount = (clone $products)->count();
        $sampleNames = (clone $products)->limit(10)->pluck('name')->all();

        $brandIds = (clone $products)->whereNotNull('brand_id')->distinct()->pluck('brand_id')->all();
        $brands = $brandIds ? Brand::whereIn('id', $brandIds)->pluck('name')->take(15)->all() : [];

        return [
            'name' => $category->name,
            'slug' => $category->slug,
            'path' => $chain,
            'children' => $children,
            'product_count' => $productCount,
            'sample_products' => $sampleNames,
            'brands' => $brands,
            'current_meta_title' => $category->meta_title,
            'current_meta_description' => $category->meta_description,
            'url' => 'https://optech.kz/ru/catalog/' . implode('/', $this->slugPath($category)),
        ];
    }

    /** id категории и всех её потомков. */
    private function branchIds(Category $category): array
    {
        $ids = [$category->id];
        $frontier = [$category->id];
        $guard = 0;

        while ($frontier && $guard++ < 10) {
            $frontier = Category::whereIn('parent_id', $frontier)->pluck('id')->all();
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }

    private function slugPath(Category $category): array
    {
        $path = [];
        $node = $category;
        $guard = 0;
        while ($node && $guard++ < 10) {
            array_unshift($path, $node->slug);
            $node = $node->parent_id ? Category::find($node->parent_id) : null;
        }

        return $path;
    }

    // -----------------------------------------------------------------------
    // Генерация
    // -----------------------------------------------------------------------

    /** @return array<string,string> */
    private function generateFor(Category $category): array
    {
        $context = $this->contextFor($category);
        $tier = $this->tierOf($category);
        $position = $this->positionOf($category);

        $system = $this->systemPrompt();
        $prompt = $this->userPrompt($context, $tier, $position);

        // Категориям вне выгрузки и тем, кого нет в индексе, семантику нужно
        // собирать поиском — у них нет ни позиций, ни готовых формулировок.
        $searchBudget = ($tier === null || $tier === 3) ? 5 : 3;
        $tools = [[
            'type' => 'web_search_20260209',
            'name' => 'web_search',
            'max_uses' => $searchBudget,
            'user_location' => [
                'type' => 'approximate',
                'country' => 'KZ',
                'city' => 'Almaty',
                'timezone' => 'Asia/Almaty',
            ],
        ]];

        $lastError = '';

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $attemptPrompt = $attempt === 1
                ? $prompt
                : $prompt . "\n\nПредыдущая попытка не прошла проверку: {$lastError}\nВерни ТОЛЬКО корректный JSON нужной формы.";

            try {
                $result = $this->claude->complete($system, $attemptPrompt, $tools);
                $this->usage['input_tokens'] += $result['usage']['input_tokens'];
                $this->usage['output_tokens'] += $result['usage']['output_tokens'];

                $this->log->info(sprintf(
                    '[%s] попытка %d: поисков %d, токенов вход/выход %d/%d',
                    $category->slug,
                    $attempt,
                    $result['searches'],
                    $result['usage']['input_tokens'],
                    $result['usage']['output_tokens']
                ));

                return $this->parseAndValidate($result['text']);
            } catch (Throwable $e) {
                $lastError = $e->getMessage();
                $this->log->warning("[{$category->slug}] попытка {$attempt}/3 не удалась: {$lastError}");
            }
        }

        throw new RuntimeException("не удалось за 3 попытки — {$lastError}");
    }

    private function systemPrompt(): string
    {
        return <<<'TXT'
Ты — SEO-копирайтер интернет-каталога OPTECH (ТОО «Оптические технологии», Алматы).
Компания — официальный дистрибьютор телекоммуникационного, измерительного и
инженерного оборудования в Казахстане: поставка, монтаж, пусконаладка,
поверка, сервис и гарантия.

Пишешь по-русски, для Google.kz. Задача — тексты, которые одинаково хорошо
читаются человеком и попадают в поисковый спрос Казахстана.

Требования к тексту:
- никакой воды и рекламных штампов вроде «широкий ассортимент по доступным ценам»;
- конкретика: типы приборов, характеристики, задачи, отрасли, бренды;
- упоминай Алматы и Казахстан естественно, а не набивкой;
- пиши только о том, что подтверждается переданными данными о категории;
- не выдумывай цены, сроки поставки, гарантийные условия и сертификаты.

Отвечай ТОЛЬКО валидным JSON-объектом, без markdown-ограждений и пояснений:
{
  "meta_title": "строка, не длиннее 60 символов",
  "meta_description": "строка, не длиннее 160 символов",
  "meta_keywords": "5-8 ключевых фраз через запятую",
  "seo_text_top": "HTML: 300-500 слов, минимум один <h2>, абзацы <p>, при необходимости <h3> и <ul><li>. Без <html>, <body> и <h1>."
}
TXT;
    }

    /** @param array<string,mixed> $context */
    private function userPrompt(array $context, ?int $tier, ?int $position): string
    {
        $lines = [
            'КАТЕГОРИЯ КАТАЛОГА',
            'Название: ' . $context['name'],
            'URL: ' . $context['url'],
            'Путь в каталоге: ' . implode(' → ', $context['path']),
            'Товаров в категории (с подкатегориями): ' . $context['product_count'],
        ];

        if ($context['children']) {
            $lines[] = 'Подкатегории: ' . implode(', ', $context['children']);
        }
        if ($context['brands']) {
            $lines[] = 'Бренды в категории: ' . implode(', ', $context['brands']);
        }
        if ($context['sample_products']) {
            $lines[] = 'Примеры товаров: ' . implode('; ', $context['sample_products']);
        }
        if (filled($context['current_meta_title'])) {
            $lines[] = 'Текущий meta_title: ' . $context['current_meta_title'];
        }

        $lines[] = '';
        $lines[] = 'ЗАДАЧА';
        $lines[] = match ($tier) {
            1 => "Категория уже в топ-10 Google.kz (позиция {$position}), цель — топ-3. "
                . 'Текст должен быть заметно полезнее, чем у конкурентов на тех же позициях. '
                . 'Обязательно и естественно используй коммерческие связки: «купить в Алматы», '
                . '«цены в Казахстане», «заказать с доставкой». Объём — 350-450 слов.',
            2 => "Категория на позиции {$position} в Google.kz — нужно вытянуть её выше. "
                . 'Дай расширенный текст: как выбирать, для каких задач, чем отличаются типы приборов. '
                . 'Используй коммерческие связки «купить в Алматы», «цены в Казахстане», '
                . '«заказать с доставкой». Объём — 400-500 слов.',
            3 => 'Категории нет в индексе Google.kz. Нужен максимально детальный текст на 500 слов: '
                . 'назначение, типы и разновидности оборудования, ключевые характеристики, '
                . 'сферы применения, критерии выбора, сопутствующие товары. '
                . 'Полный набор ключевых фраз по теме, включая коммерческие и информационные.',
            default => 'Категории нет в выгрузке позиций. Сначала собери веб-поиском реальную семантику: '
                . 'как эту продукцию ищут в Казахстане, какие формулировки и синонимы используют, '
                . 'что показывает выдача Google.kz. Затем напиши текст на 350-450 слов под собранные запросы.',
        };

        if ($tier === null || $tier === 3) {
            $lines[] = '';
            $lines[] = 'Перед написанием воспользуйся веб-поиском, чтобы уточнить терминологию '
                . 'и то, как эту категорию ищут в Казахстане.';
        }

        return implode("\n", $lines);
    }

    /** @return array<string,string> */
    private function parseAndValidate(string $raw): array
    {
        $json = $this->extractJson($raw);
        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new RuntimeException('ответ не разобрался как JSON');
        }

        foreach (['meta_title', 'meta_description', 'meta_keywords', 'seo_text_top'] as $field) {
            if (blank($data[$field] ?? null)) {
                throw new RuntimeException("в ответе нет поля {$field}");
            }
        }

        if (! preg_match('/<h2[\s>]/i', $data['seo_text_top'])) {
            throw new RuntimeException('в seo_text_top нет заголовка <h2>');
        }

        // Длины подрезаем сами: гонять модель по кругу из-за пары лишних
        // символов дороже, чем аккуратно обрезать по границе слова.
        $data['meta_title'] = $this->clip($data['meta_title'], 60);
        $data['meta_description'] = $this->clip($data['meta_description'], 160);
        $data['meta_keywords'] = $this->clipKeywords($data['meta_keywords']);

        return $data;
    }

    /** Вытаскивает JSON, даже если модель обернула его в markdown. */
    private function extractJson(string $raw): string
    {
        $raw = trim($raw);
        $raw = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $raw);

        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');

        return ($start !== false && $end !== false && $end > $start)
            ? substr($raw, $start, $end - $start + 1)
            : $raw;
    }

    private function clip(string $value, int $limit): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value));

        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        $cut = mb_substr($value, 0, $limit);
        $space = mb_strrpos($cut, ' ');

        return rtrim($space && $space > $limit * 0.6 ? mb_substr($cut, 0, $space) : $cut, " ,.;–—-");
    }

    private function clipKeywords(string $value): string
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', $value))));

        return implode(', ', array_slice($parts, 0, 8));
    }

    // -----------------------------------------------------------------------
    // Сохранение
    // -----------------------------------------------------------------------

    /**
     * @param  array<string,string>  $seo
     * @return array<int,string> список фактически обновлённых полей
     */
    private function apply(Category $category, array $seo, bool $dryRun): array
    {
        $tier = $this->tierOf($category);
        $applied = [];

        // Категории вне выгрузки позиций — это в том числе те, что уже стоят
        // высоко. Готовый meta_title у них трогать нельзя: он и так работает.
        $keepTitle = $tier === null && filled($category->meta_title) && ! $this->option('force');

        if (! $keepTitle) {
            $category->meta_title = $seo['meta_title'];
            $applied[] = 'meta_title';
        }

        $category->meta_description = $seo['meta_description'];
        $category->meta_keywords = $seo['meta_keywords'];
        $category->seo_text_top = $seo['seo_text_top'];
        $applied[] = 'meta_description';
        $applied[] = 'meta_keywords';
        $applied[] = 'seo_text_top';

        if ($dryRun) {
            $this->newLine();
            $this->line("<comment>[просмотр] {$category->slug}</comment>");
            $this->line('  title: ' . $seo['meta_title'] . ' (' . mb_strlen($seo['meta_title']) . ')');
            $this->line('  desc:  ' . $seo['meta_description'] . ' (' . mb_strlen($seo['meta_description']) . ')');
            $this->line('  keys:  ' . $seo['meta_keywords']);
            $this->line('  text:  ' . $this->wordCount($seo['seo_text_top']) . ' слов, '
                . mb_strlen($seo['seo_text_top']) . ' символов HTML');

            return $applied;
        }

        $category->save();
        $this->log->info("[{$category->slug}] сохранено: " . implode(', ', $applied));

        return $applied;
    }

    private function wordCount(string $html): int
    {
        // Теги заменяем пробелом, а не вырезаем: strip_tags склеивает последнее
        // слово заголовка с первым словом следующего абзаца
        // («<h2>Один два</h2><p>три…» -> «Один дватри…»), и объём текста
        // получается заниженным.
        $text = preg_replace('/<[^>]+>/u', ' ', $html);
        $text = html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        return $text === '' ? 0 : count(preg_split('/\s+/u', $text));
    }

    private function summary(int $updated, int $failed, bool $dryRun): void
    {
        // Цены Anthropic для claude-sonnet-4-6: $3 за 1M входных токенов,
        // $15 за 1M выходных.
        $cost = $this->usage['input_tokens'] / 1_000_000 * 3
            + $this->usage['output_tokens'] / 1_000_000 * 15;

        $this->newLine();
        $this->info(sprintf(
            '%s: %d, ошибок: %d | токенов вход/выход: %s/%s | примерно $%.2f',
            $dryRun ? 'Сгенерировано (без сохранения)' : 'Обновлено категорий',
            $updated,
            $failed,
            number_format($this->usage['input_tokens'], 0, '.', ' '),
            number_format($this->usage['output_tokens'], 0, '.', ' '),
            $cost
        ));
        $this->line('Подробный лог: storage/logs/seo-generation.log');

        $this->log->info(sprintf(
            'Итог: обновлено %d, ошибок %d, токенов %d/%d, ~$%.2f',
            $updated,
            $failed,
            $this->usage['input_tokens'],
            $this->usage['output_tokens'],
            $cost
        ));
    }
}
