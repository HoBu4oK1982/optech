import { Metadata } from 'next';
import { getTranslations } from '@/i18n/translations';
import { getBrands } from '@/lib/api';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import BrandsGrid from '@/components/brands/BrandsGrid';
import '@/components/brands/brands.css';
import '@/components/catalog/catalog.css';

export const dynamic = 'force-dynamic';

export async function generateMetadata({
  params,
}: {
  params: { locale: string };
}): Promise<Metadata> {
  const t = getTranslations(params.locale);
  return { title: t.nav.brands };
}

export default async function BrandsPage({
  params,
}: {
  params: { locale: string };
}) {
  const { locale } = params;
  const t = getTranslations(locale);
  const brands = await getBrands().catch(() => []);

  return (
    <div className="brandsPage">
      <div className="container">
        <Breadcrumbs
          items={[
            { label: t.nav.home, href: `/${locale}` },
            { label: t.nav.brands },
          ]}
        />

        <div className="brandsHead">
          <h1 className="brandsHead__title">{t.nav.brands}</h1>
          <p className="brandsHead__sub">
            Официальный дистрибьютор ведущих мировых производителей
          </p>
        </div>

        {brands?.length > 0 ? (
          <BrandsGrid brands={brands} locale={locale} />
        ) : (
          <div className="catalogEmpty">
            {locale === 'en'
              ? 'No brands here yet.'
              : locale === 'kz'
              ? 'Бұл бөлімде әзірше бренд жоқ.'
              : 'Пока нет добавленных брендов.'}
          </div>
        )}
      </div>
    </div>
  );
}
