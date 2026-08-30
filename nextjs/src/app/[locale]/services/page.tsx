export const dynamic = 'force-dynamic';

import { Metadata } from 'next';
import { getTranslations } from '@/i18n/translations';
import { getServices } from '@/lib/api';
import { truncateHtml } from '@/lib/utils';
import { BACKEND_URL } from '@/lib/constants';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import SectionGrid, { SectionItem } from '@/components/sections/SectionGrid';
import '@/components/catalog/catalog.css';

export async function generateMetadata({ params }: { params: { locale: string } }): Promise<Metadata> {
  return { title: getTranslations(params.locale).nav.services };
}

export default async function ServicesPage({ params }: { params: { locale: string } }) {
  const { locale } = params;
  const t = getTranslations(locale);
  const services = await getServices(locale).catch(() => []);

  const cta = locale === 'en' ? 'Go to section' : locale === 'kz' ? 'Бөлімге өту' : 'Перейти в раздел';
  const items: SectionItem[] = (services || []).map((s: any) => ({
    id: s.id,
    title: s.title,
    href: `/${locale}/service/${s.slug}`,
    imageUrl: s.image ? `${BACKEND_URL}/assets/images/services/${s.image}` : null,
    excerpt: s.description ? truncateHtml(s.description, 120) : null,
  }));

  return (
    <div className="catalogPage">
      <div className="container">
        <Breadcrumbs items={[{ label: t.nav.home, href: `/${locale}` }, { label: t.nav.services }]} />
        <div className="catalogHead">
          <h1 className="catalogHead__title">{t.nav.services}</h1>
        </div>
        {items.length > 0 ? (
          <SectionGrid items={items} ctaLabel={cta} variant="services" showCta={false} />
        ) : (
          <div className="catalogEmpty">
            {locale === 'en'
              ? 'No services here yet.'
              : locale === 'kz'
              ? 'Бұл бөлімде әзірше қызметтер жоқ.'
              : 'Пока нет добавленных услуг.'}
          </div>
        )}
      </div>
    </div>
  );
}
