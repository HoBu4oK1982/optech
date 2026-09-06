// ISR: контентный раздел, пересборка не чаще раза в 10 минут
// (см. комментарий про force-dynamic в app/[locale]/page.tsx).
export const revalidate = 600;
/**
 * Пустой generateStaticParams — не «заглушка», а условие включения ISR.
 * Без него Next считает маршрут с динамическим сегментом полностью
 * динамическим: страница рендерится на КАЖДЫЙ запрос и в кэш маршрутов не
 * попадает (Cache-Control: no-store). С ним страница рендерится один раз при
 * первом обращении и дальше отдаётся из кэша до истечения revalidate.
 * Список путей возвращаем пустой намеренно: прогревать весь каталог на
 * билде незачем, страницы наполняют кэш по мере обращений.
 */
export async function generateStaticParams() {
  return [];
}


import { Metadata } from 'next';
import Link from 'next/link';
import { getTranslations } from '@/i18n/translations';
import { getArticle, getArticles } from '@/lib/api';
import { BACKEND_URL } from '@/lib/constants';
import { buildMetadata, buildFaqJsonLd } from '@/lib/seo';
import { truncateHtml, absolutizeRichContent } from '@/lib/utils';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import SectionGrid, { SectionItem } from '@/components/sections/SectionGrid';
import FaqSection from '@/components/seo/FaqSection';
import { notFound } from 'next/navigation';
import './articleDetail.css';

function articleImageUrl(image?: string | null): string | null {
  if (!image) return null;
  if (image.startsWith('http')) return image;
  if (image.startsWith('/assets/')) return `${BACKEND_URL}${image}`;
  if (image.startsWith('assets/')) return `${BACKEND_URL}/${image}`;
  return `${BACKEND_URL}/assets/images/articles/${image.replace(/^\/+/, '')}`;
}

function stripHtml(value?: string | null): string {
  if (!value) return '';
  return value.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
}

function formatDate(value?: string | null, locale = 'ru'): string | null {
  if (!value) return null;
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return null;
  const intlLocale = locale === 'en' ? 'en-US' : locale === 'kz' ? 'kk-KZ' : 'ru-RU';
  return new Intl.DateTimeFormat(intlLocale, { day: '2-digit', month: 'long', year: 'numeric' }).format(date);
}

// Компактный формат для бейджа поверх обложки карточки (secCard__date) —
// в отличие от formatDate выше (полная дата в шапке статьи), тут места
// мало, а месяц словом в RU/KZ выходит слишком длинным для бейджа.
function formatCardDate(value?: string | null): string | null {
  if (!value) return null;
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return null;
  const dd = String(date.getDate()).padStart(2, '0');
  const mm = String(date.getMonth() + 1).padStart(2, '0');
  return `${dd}.${mm}.${date.getFullYear()}`;
}

function normalizeTags(tags: unknown): string[] {
  if (!tags) return [];
  if (Array.isArray(tags)) return tags.filter(Boolean).map(String).slice(0, 6);
  if (typeof tags === 'string') {
    try {
      const parsed = JSON.parse(tags);
      if (Array.isArray(parsed)) return parsed.filter(Boolean).map(String).slice(0, 6);
    } catch {
      return tags.split(',').map((tag) => tag.trim()).filter(Boolean).slice(0, 6);
    }
  }
  return [];
}

export async function generateMetadata({ params }: { params: { locale: string; article_slug: string } }): Promise<Metadata> {
  const item = await getArticle(params.article_slug, params.locale);
  if (!item) return { title: 'Not Found' };

  const image = articleImageUrl(item.og_image || item.image);
  const description = item.meta_description || item.og_description || item.excerpt || stripHtml(item.description).slice(0, 170);

  return buildMetadata({
    entity: item,
    fallbackTitle: item.title,
    fallbackDescription: description,
    ogImage: image,
    ogType: 'article',
    locale: params.locale,
    path: `/article/${params.article_slug}`,
  });
}

export default async function ArticleDetailPage({ params }: { params: { locale: string; article_slug: string } }) {
  const { locale, article_slug } = params;
  const t = getTranslations(locale);
  const item = await getArticle(article_slug, locale);
  if (!item) notFound();

  const image = articleImageUrl(item.image || item.og_image);
  const publishedDate = formatDate(item.published_at || item.created_at, locale);
  const tags = normalizeTags(item.tags);
  const intro = item.excerpt || item.meta_description || stripHtml(item.description).slice(0, 230);
  const allArticles = await getArticles(locale).catch(() => []);
  const relatedItems: SectionItem[] = (allArticles || [])
    .filter((article: any) => article.slug !== item.slug)
    .slice(0, 4)
    .map((article: any) => ({
      id: article.id,
      title: article.title,
      href: `/${locale}/article/${article.slug}`,
      imageUrl: articleImageUrl(article.image),
      excerpt: article.description ? truncateHtml(article.description, 105) : null,
      date: formatCardDate(article.published_at || article.created_at),
    }));

  const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'NewsArticle',
    headline: item.title,
    description: intro,
    image: image ? [image] : undefined,
    datePublished: item.published_at || item.created_at,
    dateModified: item.updated_at || item.published_at || item.created_at,
    author: item.author ? { '@type': 'Person', name: item.author } : { '@type': 'Organization', name: 'OPTECH' },
    publisher: { '@type': 'Organization', name: 'OPTECH' },
  };

  const faqJsonLd = buildFaqJsonLd(item.faq);

  return (
    <>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }} />
      {faqJsonLd && (
        <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd) }} />
      )}
      <main className="articlePage">
        <div className="container">
          <Breadcrumbs
            items={[
              { label: t.nav.home, href: `/${locale}` },
              { label: t.nav.articles, href: `/${locale}/articles` },
              { label: item.title },
            ]}
          />

          <section className="articleHero articleHero--compact">
            <div className="articleHero__topline">
              <span className="articleHero__kicker">Новости OPTECH</span>
              <div className="articleHero__meta" aria-label="Информация о новости">
                {publishedDate ? <span>{publishedDate}</span> : null}
                {item.reading_time ? <span>{item.reading_time} мин чтения</span> : null}
                {item.author ? <span>{item.author}</span> : <span>OPTECH</span>}
              </div>
            </div>

            <h1>{item.seo_h1 || item.title}</h1>

            {intro ? <p className="articleHero__lead">{intro}</p> : null}
          </section>

          <section className="articleLayout">
            <article className="articleContentCard">
              {item.description ? (
                <div className="rich-content articleRich" dangerouslySetInnerHTML={{ __html: absolutizeRichContent(item.description) }} />
              ) : null}
            </article>

            <aside className="articleAside" aria-label="Дополнительная информация">
              <div className="articleAside__card">
                <span className="articleAside__label">Раздел</span>
                <strong>Новости</strong>
                <p>Материалы OPTECH о технологиях, оборудовании, проектах и инженерных решениях.</p>
                <Link href={`/${locale}/articles`}>Все новости</Link>
              </div>

              {tags.length ? (
                <div className="articleAside__card articleTags">
                  <span className="articleAside__label">Темы</span>
                  <div>
                    {tags.map((tag) => (
                      <span key={tag}>{tag}</span>
                    ))}
                  </div>
                </div>
              ) : null}
            </aside>
          </section>

          {relatedItems.length ? (
            <section className="articleRelated">
              <div className="articleRelated__head">
                <span>Читайте также</span>
                <h2>Другие новости</h2>
              </div>
              <SectionGrid items={relatedItems} variant="news" showCta={false} />
            </section>
          ) : null}

          <FaqSection items={item.faq} />
        </div>
      </main>
    </>
  );
}
