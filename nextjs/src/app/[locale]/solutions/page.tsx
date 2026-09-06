// ISR: контентный раздел, пересборка не чаще раза в 10 минут
// (см. комментарий про force-dynamic в app/[locale]/page.tsx).
export const revalidate = 600;

import { Metadata } from 'next';
import { buildMetadata } from '@/lib/seo';
import { getTranslations } from '@/i18n/translations';
import { getSolCategories } from '@/lib/api';
import { truncateHtml } from '@/lib/utils';
import { BACKEND_URL } from '@/lib/constants';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import SectionGrid, { SectionItem } from '@/components/sections/SectionGrid';
import '@/components/catalog/catalog.css';

export async function generateMetadata({ params }: { params: { locale: string } }): Promise<Metadata> {
  const t = getTranslations(params.locale);
  return buildMetadata({
    locale: params.locale,
    path: '/solutions',
    fallbackTitle: t.nav.solutions,
    fallbackDescription: t.meta.solutionsDescription,
  });
}

export default async function SolutionsPage({ params }: { params: { locale: string } }) {
  const { locale } = params;
  const t = getTranslations(locale);
  const categories = await getSolCategories(locale).catch(() => []);

  const cta = locale === 'en' ? 'Go to section' : locale === 'kz' ? 'Бөлімге өту' : 'Перейти в раздел';
  const items: SectionItem[] = (categories || []).map((c: any) => ({
    id: c.id,
    title: c.title,
    href: `/${locale}/solutions/${c.slug}`,
    imageUrl: c.image ? `${BACKEND_URL}/assets/images/solcategories/${c.image}` : null,
    imageAlt: c.image_alt,
    imageTitle: c.image_title,
    excerpt: c.description ? truncateHtml(c.description, 120) : null,
  }));

  return (
    <div className="catalogPage">
      <div className="container">
        <Breadcrumbs items={[{ label: t.nav.home, href: `/${locale}` }, { label: t.nav.solutions }]} />
        <div className="catalogHead">
          <h1 className="catalogHead__title">{t.nav.solutions}</h1>
        </div>
        {items.length > 0 ? (
          <SectionGrid items={items} ctaLabel={cta} variant="solutions" showCta={false} />
        ) : (
          <div className="catalogEmpty">
            {locale === 'en'
              ? 'No solutions here yet.'
              : locale === 'kz'
              ? 'Бұл бөлімде әзірше шешімдер жоқ.'
              : 'Пока нет добавленных решений.'}
          </div>
        )}
      </div>
    </div>
  );
}
