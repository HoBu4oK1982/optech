// ISR: контентный раздел, пересборка не чаще раза в 10 минут
// (см. комментарий про force-dynamic в app/[locale]/page.tsx).
export const revalidate = 600;

import { Metadata } from 'next';
import { buildMetadata } from '@/lib/seo';
import { getTranslations } from '@/i18n/translations';
import { getServices } from '@/lib/api';
import { truncateHtml } from '@/lib/utils';
import { BACKEND_URL } from '@/lib/constants';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import SectionGrid, { SectionItem } from '@/components/sections/SectionGrid';
import '@/components/catalog/catalog.css';

export async function generateMetadata({ params }: { params: { locale: string } }): Promise<Metadata> {
  const t = getTranslations(params.locale);
  return buildMetadata({
    locale: params.locale,
    path: '/services',
    fallbackTitle: t.nav.services,
    fallbackDescription: t.meta.servicesDescription,
  });
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
    imageAlt: s.image_alt,
    imageTitle: s.image_title,
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
