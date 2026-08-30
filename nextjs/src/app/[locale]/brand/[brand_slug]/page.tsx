export const dynamic = 'force-dynamic';

import { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { getTranslations } from '@/i18n/translations';
import { getOneBrand } from '@/lib/api';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import CatalogItems, { CatalogItem } from '@/components/catalog/CatalogItems';

export async function generateMetadata({
  params,
}: {
  params: { locale: string; brand_slug: string };
}): Promise<Metadata> {
  const data = await getOneBrand(params.brand_slug);
  return {
    title: data?.brand_title || data?.brand_name || 'Brand',
    description: data?.brand_description || '',
    keywords: data?.brand_keywords || '',
  };
}

export default async function BrandPage({
  params,
}: {
  params: { locale: string; brand_slug: string };
}) {
  const { locale, brand_slug } = params;
  const t = getTranslations(locale);

  const data = await getOneBrand(brand_slug);
  if (!data) notFound();

  const brandWord = locale === 'en' ? 'Brand' : 'Бренд';

  const items: CatalogItem[] = (data.brand || []).map((p: any) => ({
    id: p.id,
    name: p.name,
    image: p.image,
    sku: p.SKU,
    kind: 'product',
    href: `/${locale}/product/${p.slug}`,
  }));

  return (
    <div className="catalogPage">
      <div className="container">
        <Breadcrumbs
          items={[
            { label: t.nav.home, href: `/${locale}` },
            { label: t.nav.brands, href: `/${locale}/brands` },
            { label: data.brand_name },
          ]}
        />

        <div className="catalogHead">
          <h1 className="catalogHead__title">
            {brandWord} — {data.brand_name}
          </h1>
        </div>

        <CatalogItems items={items} skuLabel={t.common.sku} />
      </div>
    </div>
  );
}
