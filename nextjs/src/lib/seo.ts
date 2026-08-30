import { Metadata } from 'next';
import { SITE_URL } from '@/lib/constants';

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
 * Собирает единый объект Metadata (title/description/keywords, OG,
 * canonical, robots) из SEO-полей сущности (товар/категория/статья/
 * решение/проект). Вынесено в общий хелпер, чтобы robots/canonical не
 * забывались на очередной странице по отдельности — раньше is_indexable/
 * is_followable/canonical_url заполнялись в админке, но нигде на фронте не
 * читались вообще (ни на одной странице не было тега robots).
 *
 * canonicalPath — путь БЕЗ домена, например `/ru/product/some-slug`.
 * ogImage — уже собранный абсолютный URL картинки (или null).
 */
export function buildMetadata(opts: {
  entity: SeoEntity;
  fallbackTitle: string;
  fallbackDescription?: string | null;
  ogImage?: string | null;
  canonicalPath: string;
}): Metadata {
  const { entity, fallbackTitle, fallbackDescription, ogImage, canonicalPath } = opts;

  const title = entity.meta_title || fallbackTitle;
  const description = entity.meta_description || fallbackDescription || '';
  const ogTitle = entity.og_title || title;
  const ogDescription = entity.og_description || description;

  // is_indexable/is_followable приходят с бэкенда как boolean (default true
  // в БД). !== false — на случай undefined/null в ответе API, чтобы не
  // проставить noindex по ошибке там, где поле просто не пришло.
  const index = entity.is_indexable !== false;
  const follow = entity.is_followable !== false;

  const canonical = entity.canonical_url || `${SITE_URL}${canonicalPath}`;

  return {
    title,
    description,
    keywords: entity.meta_keywords || undefined,
    alternates: { canonical },
    robots: {
      index,
      follow,
      googleBot: { index, follow },
    },
    openGraph: {
      title: ogTitle,
      description: ogDescription,
      images: ogImage ? [ogImage] : [],
      url: canonical,
      type: 'website',
    },
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
