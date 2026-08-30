export const dynamic = 'force-dynamic';

import { Metadata } from 'next';
import { getTranslations } from '@/i18n/translations';
import { getArticles } from '@/lib/api';
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
  return { title: getTranslations(params.locale).nav.articles };
}

export default async function ArticlesPage({ params }: { params: { locale: string } }) {
  const { locale } = params;
  const t = getTranslations(locale);
  const articles = await getArticles(locale).catch(() => []);

  const items: SectionItem[] = (articles || []).map((a: any) => ({
    id: a.id,
    title: a.title,
    href: `/${locale}/article/${a.slug}`,
    imageUrl: a.image ? `${BACKEND_URL}/assets/images/articles/${a.image}` : null,
    excerpt: a.description ? truncateHtml(a.description, 120) : null,
    date: formatCardDate(a.published_at || a.created_at),
  }));

  return (
    <div className="catalogPage">
      <div className="container">
        <Breadcrumbs items={[{ label: t.nav.home, href: `/${locale}` }, { label: t.nav.articles }]} />
        <div className="catalogHead">
          <h1 className="catalogHead__title">{t.nav.articles}</h1>
        </div>
        {items.length > 0 ? (
          <SectionGrid items={items} variant="news" showCta={false} />
        ) : (
          <div className="catalogEmpty">
            {locale === 'en'
              ? 'No news here yet.'
              : locale === 'kz'
              ? 'Бұл бөлімде әзірше жаңалықтар жоқ.'
              : 'Пока нет опубликованных новостей.'}
          </div>
        )}
      </div>
    </div>
  );
}
