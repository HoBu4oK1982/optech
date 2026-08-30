export const dynamic = 'force-dynamic';

import { Metadata } from 'next';
import { getTranslations } from '@/i18n/translations';
import { getOffers } from '@/lib/api';
import { truncateHtml } from '@/lib/utils';
import { BACKEND_URL } from '@/lib/constants';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import SectionGrid, { SectionItem } from '@/components/sections/SectionGrid';
import '@/components/catalog/catalog.css';

export async function generateMetadata({ params }: { params: { locale: string } }): Promise<Metadata> {
  return { title: getTranslations(params.locale).nav.offers };
}

export default async function OffersPage({ params }: { params: { locale: string } }) {
  const { locale } = params;
  const t = getTranslations(locale);
  const offers = await getOffers(locale).catch(() => []);

  const items: SectionItem[] = (offers || []).map((o: any) => ({
    id: o.id,
    title: o.title,
    href: `/${locale}/offer/${o.slug}`,
    imageUrl: o.image ? `${BACKEND_URL}/assets/images/offers/${o.image}` : null,
    excerpt: o.description ? truncateHtml(o.description, 120) : null,
  }));

  return (
    <div className="catalogPage">
      <div className="container">
        <Breadcrumbs items={[{ label: t.nav.home, href: `/${locale}` }, { label: t.nav.offers }]} />
        <div className="catalogHead">
          <h1 className="catalogHead__title">{t.nav.offers}</h1>
        </div>
        {items.length > 0 ? (
          <SectionGrid items={items} ctaLabel={t.common.readMore} />
        ) : (
          <div className="catalogEmpty">
            {locale === 'en'
              ? 'No special offers right now.'
              : locale === 'kz'
              ? 'Қазір арнайы ұсыныстар жоқ.'
              : 'Сейчас нет действующих спецпредложений.'}
          </div>
        )}
      </div>
    </div>
  );
}
