import { Metadata } from 'next';
import { SITE_URL } from '@/lib/constants';
import { locales, defaultLocale } from '@/i18n/translations';

export type SeoEntity = {
  meta_title?: string | null;
  meta_description?: string | null;
  meta_keywords?: string | null;
  og_title?: string | null;
  og_description?: string | null;
  is_indexable?: boolean | null;
  is_followable?: boolean | null;
  canonical_url?: string | null;
};

/**
 * Код локали в URL (`kz`) не совпадает с кодом языка по BCP-47 (`kk`),
 * который обязан стоять в hreflang. Раньше в hreflang уезжал именно `kz` —
 * несуществующий языковой код, из-за чего Google игнорировал связку целиком.
 */
const HREFLANG: Record<string, string> = { ru: 'ru', en: 'en', kz: 'kk' };

const OG_LOCALE: Record<string, string> = { ru: 'ru_RU', en: 'en_US', kz: 'kk_KZ' };

/** Значение атрибута <html lang>. Тот же BCP-47, что и в hreflang. */
export function htmlLang(locale: string): string {
  return HREFLANG[locale] || HREFLANG.ru;
}

/** Нормализует путь без локали: '' | '/catalog/foo' (без хвостового слэша). */
function normPath(path: string): string {
  if (!path || path === '/') return '';
  const p = path.startsWith('/') ? path : `/${path}`;
  return p.length > 1 ? p.replace(/\/+$/, '') : '';
}

export function localeUrl(locale: string, path: string): string {
  return `${SITE_URL}/${locale}${normPath(path)}`;
}

/**
 * canonical + hreflang для конкретной страницы.
 *
 * Раньше hreflang задавался ОДИН раз в `[locale]/layout.tsx` статическим
 * объектом `{ ru: '/ru', en: '/en', kk: '/kz' }`. Из-за наследования метаданных
 * этот же блок попадал на КАЖДУЮ страницу сайта: со страницы товара hreflang
 * вёл на главные страницы локалей, а не на её собственные переводы. Плюс пути
 * были относительными (Google требует абсолютные) и не было x-default.
 * Поэтому alternates собираются здесь — от реального пути страницы.
 */
export function buildAlternates(opts: {
  locale: string;
  path: string;
  canonicalOverride?: string | null;
}): Metadata['alternates'] {
  const { locale, path, canonicalOverride } = opts;

  const languages: Record<string, string> = {};
  for (const l of locales) languages[HREFLANG[l]] = localeUrl(l, path);
  languages['x-default'] = localeUrl(defaultLocale, path);

  return {
    canonical: canonicalOverride || localeUrl(locale, path),
    languages,
  };
}

/**
 * Единая сборка Metadata (title/description/keywords, OG, canonical, hreflang,
 * robots) для любой страницы — как для сущности из админки (товар/категория/
 * статья/решение/проект), так и для статической страницы без сущности.
 *
 * `path` — путь БЕЗ префикса локали: '' для главной, '/catalog/foo' и т.п.
 * Из него строятся и canonical, и все hreflang-ссылки, поэтому они физически
 * не могут разойтись между собой.
 */
export function buildMetadata(opts: {
  entity?: SeoEntity | null;
  locale: string;
  path: string;
  fallbackTitle: string;
  fallbackDescription?: string | null;
  ogImage?: string | null;
  ogType?: 'website' | 'article';
  /**
   * Не дописывать шаблон `%s | OPTECH` к тайтлу. Для главной, где тайтл уже
   * содержит бренд, иначе получалось «OPTECH — ... | OPTECH».
   */
  titleAbsolute?: boolean;
  /** Принудительный noindex — для служебных страниц (поиск и т.п.). */
  noindex?: boolean;
}): Metadata {
  const {
    entity,
    locale,
    path,
    fallbackTitle,
    fallbackDescription,
    ogImage,
    ogType = 'website',
    titleAbsolute,
    noindex,
  } = opts;

  const e = entity || {};

  const metaTitle = e.meta_title || '';
  const title = metaTitle || fallbackTitle;
  const description = e.meta_description || fallbackDescription || '';
  const ogTitle = e.og_title || title;
  const ogDescription = e.og_description || description;

  // is_indexable/is_followable приходят с бэкенда как boolean (default true
  // в БД). !== false — на случай undefined/null в ответе API, чтобы не
  // проставить noindex по ошибке там, где поле просто не пришло.
  const index = !noindex && e.is_indexable !== false;
  const follow = e.is_followable !== false;

  const alternates = buildAlternates({ locale, path, canonicalOverride: e.canonical_url });
  const canonical = (alternates as { canonical: string }).canonical;

  return {
    // meta_title заполнен вручную в админке — это законченный тайтл, шаблон
    // `%s | OPTECH` из layout к нему дописывать нельзя (переполнение по длине
    // и дубль бренда). Для автоматических тайтлов шаблон, наоборот, нужен.
    title: metaTitle || titleAbsolute ? { absolute: title } : title,
    description,
    keywords: e.meta_keywords || undefined,
    alternates,
    robots: {
      index,
      follow,
      googleBot: { index, follow },
    },
    // Next.js сливает metadata поверхностно: объект openGraph со страницы
    // ЦЕЛИКОМ перекрывает openGraph из layout. Поэтому siteName/locale
    // повторяются здесь — иначе на всех страницах, кроме главной, они
    // просто исчезали бы из разметки.
    openGraph: {
      title: ogTitle,
      description: ogDescription,
      images: ogImage ? [ogImage] : [],
      url: canonical,
      type: ogType,
      siteName: 'OPTECH',
      locale: OG_LOCALE[locale] || OG_LOCALE.ru,
      alternateLocale: locales.filter((l) => l !== locale).map((l) => OG_LOCALE[l]),
    },
  };
}

/**
 * ItemList для страниц-листингов (каталог, категории, бренды).
 *
 * Даёт Google явную структуру перечня вместо «просто сетки ссылок»: список
 * позиций с порядком и адресами. Раньше на листингах не было никакой
 * микроразметки, кроме хлебных крошек.
 *
 * items.url — путь с префиксом локали (как в href ссылок), домен добавляется
 * здесь: в разметке адреса обязаны быть абсолютными.
 */
export function buildItemListJsonLd(opts: {
  items: Array<{ name?: string | null; url?: string | null }>;
  name?: string | null;
}) {
  const list = (opts.items || []).filter((i) => i?.name && i?.url);
  if (!list.length) return null;

  return {
    '@context': 'https://schema.org',
    '@type': 'ItemList',
    name: opts.name || undefined,
    numberOfItems: list.length,
    itemListElement: list.map((item, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: item.name,
      url: (item.url as string).startsWith('http')
        ? item.url
        : `${SITE_URL}${item.url}`,
    })),
  };
}

export type FaqItem = { question?: string | null; answer?: string | null };

/**
 * FAQPage JSON-LD из массива faq ({question, answer}[]), который заполняется
 * в админке (репитер) у товара/категории/статьи/решения/проекта. Раньше это
 * поле нигде не читалось на фронте вообще.
 */
export function buildFaqJsonLd(faq?: FaqItem[] | null) {
  // Array.isArray, а не просто `faq || []` — поле faq приходит из JSON-колонки
  // в БД, и если админ никогда не трогал этот репитер, оно может быть НЕ
  // массивом (например {} вместо []). {} — truthy, `|| []` в этом случае не
  // срабатывает, а .filter на объекте кидает TypeError — что роняло всю
  // страницу целиком (SSR-краш на реальных данных категории).
  const items = (Array.isArray(faq) ? faq : []).filter((f) => f?.question && f?.answer);
  if (!items.length) return null;

  return {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: items.map((f) => ({
      '@type': 'Question',
      name: f.question,
      acceptedAnswer: { '@type': 'Answer', text: f.answer },
    })),
  };
}
