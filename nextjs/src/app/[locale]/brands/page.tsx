import { Metadata } from 'next';
import { buildItemListJsonLd, buildMetadata } from '@/lib/seo';
import { getTranslations } from '@/i18n/translations';
import { getBrands } from '@/lib/api';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import BrandsGrid from '@/components/brands/BrandsGrid';
import '@/components/brands/brands.css';
import '@/components/catalog/catalog.css';

// ISR: страница пересобирается не чаще раза в 5 минут. Раньше здесь стоял
// force-dynamic — он не только рендерил страницу на каждый запрос, но и
// отключал кэш fetch (next.revalidate в lib/api.ts игнорировался), так что
// каждый заход бота бил в Laravel напрямую.
export const revalidate = 300;

export async function generateMetadata({
  params,
}: {
  params: { locale: string };
}): Promise<Metadata> {
  const t = getTranslations(params.locale);
  return buildMetadata({
    locale: params.locale,
    path: '/brands',
    fallbackTitle: t.nav.brands,
    fallbackDescription: t.meta.brandsDescription,
  });
}

export default async function BrandsPage({
  params,
}: {
  params: { locale: string };
}) {
  const { locale } = params;
  const t = getTranslations(locale);
  const brands = await getBrands(locale).catch(() => []);

  const itemListJsonLd = buildItemListJsonLd({
    items: (brands || []).map((b: any) => ({
      name: b.name,
      url: `/${locale}/brand/${b.slug}`,
    })),
    name: t.nav.brands,
  });

  return (
    <div className="brandsPage">
      {itemListJsonLd && (
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(itemListJsonLd) }}
        />
      )}
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
