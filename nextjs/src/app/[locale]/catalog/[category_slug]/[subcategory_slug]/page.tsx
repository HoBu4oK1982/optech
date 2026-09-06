// ISR: страница пересобирается не чаще раза в 5 минут. Раньше здесь стоял
// force-dynamic — он не только рендерил страницу на каждый запрос, но и
// отключал кэш fetch (next.revalidate в lib/api.ts игнорировался), так что
// каждый заход бота бил в Laravel напрямую.
export const revalidate = 300;
// generateStaticParams здесь НЕТ намеренно. Страницы категорий читают
// searchParams (?sort=), а это несовместимо со статической генерацией: с
// generateStaticParams Next помечает маршрут как пререндеримый и падает на
// запросе с DYNAMIC_SERVER_USAGE — страница отдаёт 500. Категории остаются
// динамическими; нагрузку с Laravel снимает кэш fetch (60 с, см. lib/api.ts).
// Перевести их на ISR можно, только унеся сортировку на клиент.


import { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { getTranslations } from '@/i18n/translations';
import { getSubCategory, getProductsByCategory } from '@/lib/api';
import { BACKEND_URL } from '@/lib/constants';
import { buildMetadata, buildFaqJsonLd, buildItemListJsonLd } from '@/lib/seo';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import CatalogItems, { CatalogItem } from '@/components/catalog/CatalogItems';
import CatalogSortToggle from '@/components/catalog/CatalogSortToggle';
import FaqSection from '@/components/seo/FaqSection';
import SeoText from '@/components/seo/SeoText';
import { absolutizeRichContent } from '@/lib/utils';

const catImg = (image?: string | null) =>
  image ? `${BACKEND_URL}/assets/images/categories/${image}` : null;

export async function generateMetadata({
  params,
}: {
  params: { locale: string; category_slug: string; subcategory_slug: string };
}): Promise<Metadata> {
  const data = await getSubCategory(params.category_slug, params.subcategory_slug, params.locale);
  if (!data?.subcategory) return { title: 'Not Found' };
  const s = data.subcategory;
  return buildMetadata({
    entity: s,
    fallbackTitle: s.name,
    ogImage: catImg(s.og_image || s.image),
    locale: params.locale,
    path: `/catalog/${params.category_slug}/${params.subcategory_slug}`,
  });
}

export default async function SubcategoryPage({
  params,
  searchParams,
}: {
  params: { locale: string; category_slug: string; subcategory_slug: string };
  searchParams?: { sort?: string };
}) {
  const { locale, category_slug, subcategory_slug } = params;
  const t = getTranslations(locale);
  const sort = searchParams?.sort;

  const data = await getSubCategory(category_slug, subcategory_slug, locale);
  if (!data?.subcategory) notFound();

  const { subcategory, pcategory } = data;
  const hasChildren = !!(subcategory.children && subcategory.children.length > 0);

  let products: any[] = [];
  if (!hasChildren) {
    try {
      products = await getProductsByCategory(subcategory_slug, sort);
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
    ? subcategory.children.map((sub: any) => ({
        id: sub.id,
        name: sub.name,
        image: sub.image,
        imageAlt: sub.image_alt,
        imageTitle: sub.image_title,
        kind: 'category',
        href: `/${locale}/catalog/${category_slug}/${subcategory_slug}/${sub.slug}`,
      }))
    : products.map((p: any) => ({
        id: p.id,
        name: p.name,
        image: p.image,
        imageAlt: p.image_alt,
        imageTitle: p.image_title,
        sku: p.SKU,
        kind: 'product',
        href: `/${locale}/product/${p.slug}`,
      }));

  const faqJsonLd = buildFaqJsonLd(subcategory.faq);

  const itemListJsonLd = buildItemListJsonLd({
    items: items.map((i) => ({ name: i.name, url: i.href })),
    name: subcategory.seo_h1 || subcategory.name,
  });

  return (
    <div className="catalogPage">
      {faqJsonLd && (
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd) }}
        />
      )}
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
            { label: t.nav.catalog, href: `/${locale}/catalog` },
            {
              label: pcategory?.breadcrumb_title || pcategory?.name || '',
              href: `/${locale}/catalog/${category_slug}`,
            },
            { label: subcategory.breadcrumb_title || subcategory.name },
          ]}
        />

        <div className="catalogHead">
          <h1 className="catalogHead__title">{subcategory.seo_h1 || subcategory.name}</h1>
        </div>

        <SeoText html={subcategory.seo_text_top} variant="top" />

        {!hasChildren && items.length > 1 && (
          <CatalogSortToggle
            basePath={`/${locale}/catalog/${category_slug}/${subcategory_slug}`}
            current={sort}
          />
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

        {subcategory.description && (
          <div
            className="rich-content catalogDesc"
            dangerouslySetInnerHTML={{ __html: absolutizeRichContent(subcategory.description) }}
          />
        )}

        <SeoText
          heading={subcategory.seo_h2}
          html={subcategory.seo_text_bottom}
          variant="bottom"
        />

        <FaqSection items={subcategory.faq} />
      </div>
    </div>
  );
}
