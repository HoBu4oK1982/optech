export const dynamic = 'force-dynamic';

import { Metadata } from 'next';
import { getTranslations } from '@/i18n/translations';
import { getAllCategories } from '@/lib/api';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import CatalogItems, { CatalogItem } from '@/components/catalog/CatalogItems';

export async function generateMetadata({
  params,
}: {
  params: { locale: string };
}): Promise<Metadata> {
  const t = getTranslations(params.locale);
  return {
    title: t.nav.catalog,
    description: `${t.nav.catalog} — OPTECH`,
  };
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

  return (
    <div className="catalogPage">
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
