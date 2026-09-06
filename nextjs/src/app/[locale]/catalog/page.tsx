// ISR: страница пересобирается не чаще раза в 5 минут. Раньше здесь стоял
// force-dynamic — он не только рендерил страницу на каждый запрос, но и
// отключал кэш fetch (next.revalidate в lib/api.ts игнорировался), так что
// каждый заход бота бил в Laravel напрямую.
export const revalidate = 300;

import { Metadata } from 'next';
import { buildMetadata } from '@/lib/seo';
import { getTranslations } from '@/i18n/translations';
import { getAllCategories } from '@/lib/api';
import { buildItemListJsonLd } from '@/lib/seo';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import CatalogItems, { CatalogItem } from '@/components/catalog/CatalogItems';

export async function generateMetadata({
  params,
}: {
  params: { locale: string };
}): Promise<Metadata> {
  const t = getTranslations(params.locale);
  return buildMetadata({
    locale: params.locale,
    path: '/catalog',
    fallbackTitle: t.nav.catalog,
    fallbackDescription: t.meta.catalogDescription,
  });
}

export default async function CatalogPage({
  params,
}: {
  params: { locale: string };
}) {
  const { locale } = params;
  const t = getTranslations(locale);
  const categories = await getAllCategories(locale);
  const cta = locale === 'en' ? 'Go to section' : locale === 'kz' ? 'Бөлімге өту' : 'Перейти в раздел';

  const items: CatalogItem[] = (categories || []).map((c: any) => ({
    id: c.id,
    name: c.name,
    image: c.image,
    kind: 'category',
    href: `/${locale}/catalog/${c.slug}`,
  }));

  const itemListJsonLd = buildItemListJsonLd({
    items: items.map((i) => ({ name: i.name, url: i.href })),
    name: t.nav.catalog,
  });

  return (
    <div className="catalogPage">
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
            { label: t.nav.catalog },
          ]}
        />

        <div className="catalogHead">
          <h1 className="catalogHead__title">{t.nav.catalog}</h1>
        </div>

        <CatalogItems
          items={items}
          categoryVariant="button"
          ctaCategory={cta}
          skuLabel={t.common.sku}
        />
      </div>
    </div>
  );
}
