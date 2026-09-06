import { SITE_URL } from '@/lib/constants';
import { locales, defaultLocale } from '@/i18n/translations';
import { htmlLang, localeUrl } from '@/lib/seo';
import {
  getBrands,
  getProjects,
  getArticles,
  getKnowledgeArticles,
  getServices,
  getOffers,
  getSolCategories,
  getCategoriesForSitemap,
  getProductsForSitemap,
  getSolutionsForSitemap,
} from '@/lib/api';

/**
 * Карта сайта: индекс + отдельные под-карты по типам контента.
 *
 * Раньше это был один файл на весь сайт, где lastmod у половины разделов
 * подставлялся как new Date() — то есть каждая перегенерация сообщала
 * поисковику, что обновились ВСЕ страницы сразу. Такой сигнал бесполезен:
 * бот перестаёт ему верить. Теперь lastmod берётся из updated_at записи.
 */

export type SitemapItem = {
  /** Путь БЕЗ префикса локали: '' для главной, '/catalog/foo' и т.д. */
  path: string;
  lastmod?: string | null;
  changefreq?: string | null;
  priority?: number | null;
};

/** Больше одной под-карты на тип, если записей стало слишком много. */
const MAX_URLS_PER_FILE = 45000;

const VALID_FREQ = new Set([
  'always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never',
]);

/**
 * Дефолты колонки sitemap_priority в БД (0.5 у старых таблиц, 0.6 у
 * добавленных позже). Ни одна запись в админке их не меняла, поэтому слепо
 * доверять этому полю нельзя: у всех 636 товаров и 115 категорий там сейчас
 * одно и то же значение, и приоритеты по типам страниц просто исчезли бы.
 * Значение из админки считаем осознанным, только если оно отличается от
 * дефолта.
 */
const COLUMN_DEFAULT_PRIORITIES = new Set(['0.5', '0.6', '0.50', '0.60']);

function resolvePriority(raw: unknown, fallback: number): number {
  if (raw === null || raw === undefined || raw === '') return fallback;
  if (COLUMN_DEFAULT_PRIORITIES.has(String(raw))) return fallback;
  const n = Number(raw);
  return Number.isFinite(n) && n >= 0 && n <= 1 ? n : fallback;
}

function resolveFreq(raw: unknown, fallback: string): string {
  return typeof raw === 'string' && VALID_FREQ.has(raw) ? raw : fallback;
}

/** ISO-дата без миллисекунд; null, если значение непригодно. */
function isoDate(value: unknown): string | null {
  if (!value) return null;
  const d = new Date(value as string);
  if (Number.isNaN(d.getTime())) return null;
  return d.toISOString().replace(/\.\d{3}Z$/, '+00:00');
}

function xmlEscape(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;');
}

/** Индексируемая и не выключенная из карты запись. */
function inSitemap(item: any): boolean {
  return item?.in_sitemap !== false && item?.is_indexable !== false;
}

/**
 * Разворачивает одну запись в три <url> — по одному на локаль, и в каждом
 * полный набор xhtml:link на все языковые версии. Именно так Google просит
 * связывать переводы в карте сайта: перечислить каждую версию отдельно и в
 * каждой сослаться на все остальные, включая саму себя и x-default.
 */
function renderItem(item: SitemapItem): string {
  const alternates = [
    ...locales.map((l) => `    <xhtml:link rel="alternate" hreflang="${htmlLang(l)}" href="${xmlEscape(localeUrl(l, item.path))}"/>`),
    `    <xhtml:link rel="alternate" hreflang="x-default" href="${xmlEscape(localeUrl(defaultLocale, item.path))}"/>`,
  ].join('\n');

  return locales
    .map((locale) => {
      const lines = [
        '  <url>',
        `    <loc>${xmlEscape(localeUrl(locale, item.path))}</loc>`,
      ];
      if (item.lastmod) lines.push(`    <lastmod>${item.lastmod}</lastmod>`);
      if (item.changefreq) lines.push(`    <changefreq>${item.changefreq}</changefreq>`);
      if (item.priority != null) lines.push(`    <priority>${item.priority.toFixed(1)}</priority>`);
      lines.push(alternates, '  </url>');
      return lines.join('\n');
    })
    .join('\n');
}

export function renderUrlset(items: SitemapItem[]): string {
  return [
    '<?xml version="1.0" encoding="UTF-8"?>',
    '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">',
    ...items.map(renderItem),
    '</urlset>',
    '',
  ].join('\n');
}

export function renderSitemapIndex(entries: Array<{ loc: string; lastmod?: string | null }>): string {
  return [
    '<?xml version="1.0" encoding="UTF-8"?>',
    '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
    ...entries.map((e) => {
      const lines = ['  <sitemap>', `    <loc>${xmlEscape(e.loc)}</loc>`];
      if (e.lastmod) lines.push(`    <lastmod>${e.lastmod}</lastmod>`);
      lines.push('  </sitemap>');
      return lines.join('\n');
    }),
    '</sitemapindex>',
    '',
  ].join('\n');
}

export function xmlResponse(body: string): Response {
  return new Response(body, {
    headers: {
      'Content-Type': 'application/xml; charset=utf-8',
      'Cache-Control': 'public, max-age=0, s-maxage=3600, stale-while-revalidate=86400',
    },
  });
}

// ---------------------------------------------------------------------------
// Источники данных
// ---------------------------------------------------------------------------

/**
 * Статические страницы. Приоритеты заданы вручную: главная — 1.0, каталог —
 * 0.9 (он выше отдельных категорий), разделы-хабы — 0.7, справочно-правовые
 * страницы — 0.3.
 */
const STATIC_PAGES: Array<{ path: string; priority: number; changefreq: string }> = [
  { path: '', priority: 1.0, changefreq: 'daily' },
  { path: '/catalog', priority: 0.9, changefreq: 'daily' },
  { path: '/brands', priority: 0.7, changefreq: 'weekly' },
  { path: '/solutions', priority: 0.7, changefreq: 'weekly' },
  { path: '/services', priority: 0.7, changefreq: 'weekly' },
  { path: '/projects', priority: 0.7, changefreq: 'weekly' },
  { path: '/articles', priority: 0.7, changefreq: 'weekly' },
  { path: '/basa-znani', priority: 0.7, changefreq: 'weekly' },
  { path: '/offers', priority: 0.7, changefreq: 'weekly' },
  { path: '/about', priority: 0.5, changefreq: 'monthly' },
  { path: '/contacts', priority: 0.5, changefreq: 'monthly' },
  { path: '/license', priority: 0.3, changefreq: 'yearly' },
  { path: '/terms', priority: 0.3, changefreq: 'yearly' },
  { path: '/privacy', priority: 0.3, changefreq: 'yearly' },
];

async function safe<T>(promise: Promise<T>): Promise<T | []> {
  try {
    return await promise;
  } catch {
    return [];
  }
}

/** Приводит запись раздела к SitemapItem. */
function toItem(item: any, path: string, fallbackPriority: number, fallbackFreq: string): SitemapItem {
  return {
    path,
    lastmod: isoDate(item?.updated_at),
    changefreq: resolveFreq(item?.sitemap_changefreq, fallbackFreq),
    priority: resolvePriority(item?.sitemap_priority, fallbackPriority),
  };
}

export const SITEMAP_SOURCES = {
  pages: async (): Promise<SitemapItem[]> =>
    STATIC_PAGES.map((p) => ({ path: p.path, priority: p.priority, changefreq: p.changefreq })),

  categories: async (): Promise<SitemapItem[]> => {
    const rows = (await safe(getCategoriesForSitemap())) as any[];
    return (rows || []).filter(inSitemap).map((c) => {
      const path = Array.isArray(c.path) ? c.path.join('/') : c.slug;
      return toItem(c, `/catalog/${path}`, 0.8, 'weekly');
    });
  },

  products: async (): Promise<SitemapItem[]> => {
    const rows = (await safe(getProductsForSitemap())) as any[];
    return (rows || []).filter(inSitemap).map((p) => toItem(p, `/product/${p.slug}`, 0.7, 'weekly'));
  },

  articles: async (): Promise<SitemapItem[]> => {
    const rows = (await safe(getArticles('ru'))) as any[];
    return (rows || []).filter(inSitemap).map((a) => toItem(a, `/article/${a.slug}`, 0.6, 'monthly'));
  },

  // База знаний — материалы articles с type = knowledge_base.
  knowledge: async (): Promise<SitemapItem[]> => {
    const rows = (await safe(getKnowledgeArticles('ru'))) as any[];
    return (rows || []).filter(inSitemap).map((a) => toItem(a, `/basa-znani/${a.slug}`, 0.6, 'monthly'));
  },

  solutions: async (): Promise<SitemapItem[]> => {
    const [cats, sols] = await Promise.all([
      safe(getSolCategories('ru')) as Promise<any[]>,
      safe(getSolutionsForSitemap()) as Promise<any[]>,
    ]);
    return [
      ...(cats || []).filter(inSitemap).map((c) => toItem(c, `/solutions/${c.slug}`, 0.7, 'weekly')),
      ...(sols || [])
        .filter(inSitemap)
        .map((s) => toItem(s, `/solutions/${s.category_slug}/${s.slug}`, 0.6, 'weekly')),
    ];
  },

  brands: async (): Promise<SitemapItem[]> => {
    const rows = (await safe(getBrands())) as any[];
    return (rows || []).filter(inSitemap).map((b) => toItem(b, `/brand/${b.slug}`, 0.6, 'weekly'));
  },

  services: async (): Promise<SitemapItem[]> => {
    const rows = (await safe(getServices('ru'))) as any[];
    return (rows || []).filter(inSitemap).map((s) => toItem(s, `/service/${s.slug}`, 0.6, 'monthly'));
  },

  offers: async (): Promise<SitemapItem[]> => {
    const rows = (await safe(getOffers('ru'))) as any[];
    return (rows || []).filter(inSitemap).map((o) => toItem(o, `/offer/${o.slug}`, 0.5, 'monthly'));
  },

  projects: async (): Promise<SitemapItem[]> => {
    const rows = (await safe(getProjects('ru'))) as any[];
    return (rows || []).filter(inSitemap).map((p) => toItem(p, `/project/${p.slug}`, 0.5, 'monthly'));
  },
};

export type SitemapType = keyof typeof SITEMAP_SOURCES;

export const SITEMAP_TYPES = Object.keys(SITEMAP_SOURCES) as SitemapType[];

/**
 * Сколько файлов нужно разделу: одна запись даёт по одному <url> на каждую
 * локаль, поэтому в лимит 50 000 URL упираемся втрое быстрее, чем кажется.
 */
export function partsCount(itemCount: number): number {
  const urls = itemCount * locales.length;
  return Math.max(1, Math.ceil(urls / MAX_URLS_PER_FILE));
}

export function sliceForPart(items: SitemapItem[], part: number): SitemapItem[] {
  const perFile = Math.floor(MAX_URLS_PER_FILE / locales.length);
  return items.slice((part - 1) * perFile, part * perFile);
}

/** Самая свежая дата раздела — она уходит в <lastmod> индекса. */
export function latestLastmod(items: SitemapItem[]): string | null {
  let best: string | null = null;
  for (const item of items) {
    if (item.lastmod && (!best || item.lastmod > best)) best = item.lastmod;
  }
  return best;
}

export function sitemapUrl(type: string, part: number): string {
  return `${SITE_URL}/sitemap/${type}${part > 1 ? `-${part}` : ''}.xml`;
}
