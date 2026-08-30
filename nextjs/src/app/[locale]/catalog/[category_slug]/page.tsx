export const dynamic = 'force-dynamic';

import { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { getTranslations } from '@/i18n/translations';
import { getCategory, getProductsByCategory } from '@/lib/api';
import { BACKEND_URL } from '@/lib/constants';
import { buildMetadata, buildFaqJsonLd } from '@/lib/seo';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import CatalogItems, { CatalogItem } from '@/components/catalog/CatalogItems';
import CatalogSortToggle from '@/components/catalog/CatalogSortToggle';
import FaqSection from '@/components/seo/FaqSection';
import { absolutizeRichContent } from '@/lib/utils';

const catImg = (image?: string | null) =>
  image ? `${BACKEND_URL}/assets/images/categories/${image}` : null;

export async function generateMetadata({
  params,
}: {
  params: { locale: string; category_slug: string };
}): Promise<Metadata> {
  const category = await getCategory(params.category_slug, params.locale);
  if (!category) return { title: 'Not Found' };
  return buildMetadata({
    entity: category,
    fallbackTitle: category.name,
    ogImage: catImg(category.og_image || category.image),
    canonicalPath: `/${params.locale}/catalog/${params.category_slug}`,
  });
}

export default async function CategoryPage({
  params,
  searchParams,
}: {
  params: { locale: string; category_slug: string };
  searchParams?: { sort?: string };
}) {
  const { locale, category_slug } = params;
  const t = getTranslations(locale);
  const sort = searchParams?.sort;

  const category = await getCategory(category_slug, locale);
  if (!category) notFound();

  const hasChildren = !!(category.children && category.children.length > 0);

  let products: any[] = [];
  if (!hasChildren) {
    try {
      products = await getProductsByCategory(category_slug, sort);
    } catch (e) {
      console.error(e);
    }
  }

  const ctaCategory =
    locale === 'en' ? 'Go to section' : locale === 'kz' ? 'Бөлімге өту' : 'Перейти в раздел';
  const emptyText =
    locale === 'en'
      ? 'No products in this section yet.'
      : locale === 'kz'
      ? 'Бұл бөлімде әзірше тауарлар жоқ.'
      : 'В этом разделе пока нет товаров.';

  const items: CatalogItem[] = hasChildren
    ? category.children.map((sub: any) => ({
        id: sub.id,
        name: sub.name,
        image: sub.image,
        kind: 'category',
        href: `/${locale}/catalog/${category_slug}/${sub.slug}`,
      }))
    : products.map((p: any) => ({
        id: p.id,
        name: p.name,
        image: p.image,
        sku: p.SKU,
        kind: 'product',
        href: `/${locale}/product/${p.slug}`,
      }));

  const faqJsonLd = buildFaqJsonLd(category.faq);

  return (
    <div className="catalogPage">
      {faqJsonLd && (
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd) }}
        />
      )}
      <div className="container">
        <Breadcrumbs
          items={[
            { label: t.nav.home, href: `/${locale}` },
            { label: t.nav.catalog, href: `/${locale}/catalog` },
            { label: category.name },
          ]}
        />

        <div className="catalogHead">
          <h1 className="catalogHead__title">{category.seo_h1 || category.name}</h1>
        </div>

        {!hasChildren && items.length > 1 && (
          <CatalogSortToggle basePath={`/${locale}/catalog/${category_slug}`} current={sort} />
        )}

        {items.length > 0 ? (
          <CatalogItems
            items={items}
            ctaCategory={ctaCategory}
            ctaProduct={t.common.readMore}
            skuLabel={t.common.sku}
          />
        ) : (
          <div className="catalogEmpty">{emptyText}</div>
        )}

        {category.description && (
          <div
            className="rich-content catalogDesc"
            dangerouslySetInnerHTML={{ __html: absolutizeRichContent(category.description) }}
          />
        )}

        <FaqSection items={category.faq} />
      </div>
    </div>
  );
}
