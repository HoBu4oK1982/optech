// ISR: контентный раздел, пересборка не чаще раза в 10 минут
// (см. комментарий про force-dynamic в app/[locale]/page.tsx).
export const revalidate = 600;

import { Metadata } from 'next';
import { buildMetadata } from '@/lib/seo';
import { getTranslations } from '@/i18n/translations';
import { getKnowledgeArticles } from '@/lib/api';
import { truncateHtml } from '@/lib/utils';
import { BACKEND_URL } from '@/lib/constants';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import SectionGrid, { SectionItem } from '@/components/sections/SectionGrid';
import '@/components/catalog/catalog.css';

function formatCardDate(value?: string | null): string | null {
  if (!value) return null;
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return null;
  const dd = String(date.getDate()).padStart(2, '0');
  const mm = String(date.getMonth() + 1).padStart(2, '0');
  return `${dd}.${mm}.${date.getFullYear()}`;
}

export async function generateMetadata({ params }: { params: { locale: string } }): Promise<Metadata> {
  const t = getTranslations(params.locale);
  return buildMetadata({
    locale: params.locale,
    path: '/basa-znani',
    fallbackTitle: t.nav.knowledgeBase,
    fallbackDescription: t.meta.knowledgeDescription,
  });
}

export default async function KnowledgeBasePage({ params }: { params: { locale: string } }) {
  const { locale } = params;
  const t = getTranslations(locale);

  // Бэкенд для «Базы знаний» ещё не готов (см. lib/api.ts) — до его подключения
  // здесь просто пустой список, страница/раздел уже существуют и готовы
  // принять контент, как только появится API.
  const articles = await getKnowledgeArticles(locale).catch(() => []);

  const items: SectionItem[] = (articles || []).map((a: any) => ({
    id: a.id,
    title: a.title,
    href: `/${locale}/basa-znani/${a.slug}`,
    imageUrl: a.image ? `${BACKEND_URL}/assets/images/articles/${a.image}` : null,
    imageAlt: a.image_alt,
    imageTitle: a.image_title,
    excerpt: a.description ? truncateHtml(a.description, 120) : null,
    date: formatCardDate(a.published_at || a.created_at),
  }));

  return (
    <div className="catalogPage">
      <div className="container">
        <Breadcrumbs items={[{ label: t.nav.home, href: `/${locale}` }, { label: t.nav.knowledgeBase }]} />
        <div className="catalogHead">
          <h1 className="catalogHead__title">{t.nav.knowledgeBase}</h1>
        </div>

        {items.length > 0 ? (
          <SectionGrid items={items} variant="news" showCta={false} />
        ) : (
          <div className="catalogEmpty">
            {locale === 'en'
              ? 'No articles here yet.'
              : locale === 'kz'
              ? 'Бұл бөлімде әзірше мақалалар жоқ.'
              : 'В этом разделе пока нет статей.'}
          </div>
        )}
      </div>
    </div>
  );
}
