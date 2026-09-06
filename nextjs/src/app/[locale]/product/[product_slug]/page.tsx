// ISR: страница пересобирается не чаще раза в 5 минут. Раньше здесь стоял
// force-dynamic — он не только рендерил страницу на каждый запрос, но и
// отключал кэш fetch (next.revalidate в lib/api.ts игнорировался), так что
// каждый заход бота бил в Laravel напрямую.
export const revalidate = 300;

/**
 * Пустой generateStaticParams — не «заглушка», а условие включения ISR.
 * Без него Next считает маршрут с динамическим сегментом полностью
 * динамическим: страница рендерится на КАЖДЫЙ запрос и в кэш маршрутов не
 * попадает (Cache-Control: no-store). С ним страница рендерится один раз при
 * первом обращении и дальше отдаётся из кэша до истечения revalidate.
 * Список путей возвращаем пустой намеренно: прогревать весь каталог на
 * билде незачем, страницы наполняют кэш по мере обращений.
 */
export async function generateStaticParams() {
  return [];
}

import { Metadata } from 'next';
import Image from 'next/image';
import Link from 'next/link';
import { notFound } from 'next/navigation';
import { getTranslations } from '@/i18n/translations';
import { getProduct } from '@/lib/api';
import { BACKEND_URL, SITE_URL } from '@/lib/constants';
import { buildMetadata, buildFaqJsonLd } from '@/lib/seo';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import CatalogItems, { CatalogItem } from '@/components/catalog/CatalogItems';
import ProductTabs, { ProductTab } from '@/components/product/ProductTabs';
import ProductInquiry from '@/components/product/ProductInquiry';
import FaqSection from '@/components/seo/FaqSection';
import '@/components/product/product.css';

const productImg = (image?: string | null) =>
  image ? `${BACKEND_URL}/assets/images/products/${image}` : undefined;

type Crumb = { name: string; slug: string };

/**
 * Строит цепочку категорий товара из ответа API — защищённо, под разные
 * возможные формы (getProduct может отдавать по-разному):
 *   1) product.categories / data.categories — упорядоченный массив (корень → лист)
 *   2) product.category с рекурсивным .parent / .pcategory
 *   3) плоские поля ответа: pcategory / category / subcategory / subsubcategory
 * Если категории в ответе нет — вернёт [] и крошки останутся Главная / Каталог / Товар.
 */
function extractCategoryChain(product: any, data: any): Crumb[] {
  const norm = (c: any): Crumb | null =>
    c && c.slug && c.name ? { name: c.name, slug: c.slug } : null;

  const arr = product?.categories ?? data?.categories;
  if (Array.isArray(arr) && arr.length) {
    return arr.map(norm).filter(Boolean) as Crumb[];
  }

  const chain: Crumb[] = [];
  const seen = new Set<string>();
  let node: any = product?.category ?? data?.category ?? data?.subcategory ?? null;
  while (node) {
    const c = norm(node);
    if (!c || seen.has(c.slug)) break;
    seen.add(c.slug);
    chain.unshift(c);
    node = node.parent ?? node.pcategory ?? node.parentCategory ?? null;
  }
  if (chain.length) return chain;

  const flat: Crumb[] = [];
  for (const key of ['pcategory', 'category', 'subcategory', 'subsubcategory']) {
    const c = norm(data?.[key] ?? product?.[key]);
    if (c && !flat.some((x) => x.slug === c.slug)) flat.push(c);
  }
  return flat;
}

const CURRENCY_SYMBOL: Record<string, string> = { KZT: '₸', USD: '$', EUR: '€', RUB: '₽' };

function formatPrice(value: number | string, currency = 'KZT'): string {
  const num = Number(value);
  const formatted = Number.isFinite(num) ? new Intl.NumberFormat('ru-RU').format(num) : String(value);
  return `${formatted} ${CURRENCY_SYMBOL[currency] || currency}`;
}

const AVAILABILITY_SCHEMA: Record<string, string> = {
  InStock: 'https://schema.org/InStock',
  OutOfStock: 'https://schema.org/OutOfStock',
  PreOrder: 'https://schema.org/PreOrder',
  BackOrder: 'https://schema.org/BackOrder',
};

export async function generateMetadata({
  params,
}: {
  params: { locale: string; product_slug: string };
}): Promise<Metadata> {
  const data = await getProduct(params.product_slug, params.locale);
  if (!data?.product) return { title: 'Not Found' };
  const p = data.product;
  const img = productImg(p.og_image || p.image) || null;

  return buildMetadata({
    entity: p,
    fallbackTitle: p.name,
    ogImage: img,
    locale: params.locale,
    path: `/product/${params.product_slug}`,
  });
}

export default async function ProductPage({
  params,
}: {
  params: { locale: string; product_slug: string };
}) {
  const { locale, product_slug } = params;
  const t = getTranslations(locale);

  const data = await getProduct(product_slug, locale);
  if (!data?.product) notFound();

  const { product, brand, related } = data;
  const imgSrc = productImg(product.image);
  const imgAlt = product.image_alt || product.name;

  // Хлебные крошки: Главная / Каталог / <категории товара...> / Товар
  const catChain = extractCategoryChain(product, data);
  const catCrumbs: { label: string; href: string }[] = [];
  let catPath = `/${locale}/catalog`;
  for (const c of catChain) {
    catPath += `/${c.slug}`;
    catCrumbs.push({ label: c.name, href: catPath });
  }
  const breadcrumbItems = [
    { label: t.nav.home, href: `/${locale}` },
    { label: t.nav.catalog, href: `/${locale}/catalog` },
    ...catCrumbs,
    { label: product.name },
  ];

  // Вкладка «Информация для заказа» — теперь показывается всегда, даже если
  // usage у товара не заполнен (раньше вкладка просто пропадала целиком).
  const orderInfoEmptyText =
    locale === 'en'
      ? '<p>Ordering information for this product will be added soon. Contact us for details.</p>'
      : locale === 'kz'
      ? '<p>Бұл тауар бойынша тапсырыс беру ақпараты жақында қосылады. Толығырақ біздермен байланысыңыз.</p>'
      : '<p>Информация для заказа этого товара пока не заполнена. Уточните детали у наших менеджеров.</p>';

  const tabs: ProductTab[] = [
    product.description && { id: 'desc', label: t.product.tabDescription, shortLabel: t.product.tabDescriptionShort, html: product.description },
    product.char && { id: 'char', label: t.product.tabCharacteristics, shortLabel: t.product.tabCharacteristicsShort, html: product.char },
    { id: 'order', label: t.product.tabOrderInfo, shortLabel: t.product.tabOrderInfoShort, html: product.usage || orderInfoEmptyText },
  ].filter(Boolean) as ProductTab[];

  // Recommended products -> same card system as catalog
  const relatedItems: CatalogItem[] = (related || []).map((item: any) => ({
    id: item.id,
    name: item.name,
    image: item.image,
    imageAlt: item.image_alt,
    imageTitle: item.image_title,
    sku: item.SKU,
    kind: 'product',
    href: `/${locale}/product/${item.slug || item.id}`,
  }));

  const currency = product.currency || 'KZT';
  const availabilityKey: string = product.availability || 'InStock';
  const hasPrice = !product.price_on_request && product.price != null && product.price !== '';

  const offer: Record<string, any> = {
    '@type': 'Offer',
    url: `${SITE_URL}/${locale}/product/${product_slug}`,
    priceCurrency: currency,
    availability: AVAILABILITY_SCHEMA[availabilityKey] || AVAILABILITY_SCHEMA.InStock,
  };
  // price — обязательное поле валидного Offer; если товар "цена по запросу"
  // (или не заполнена), Offer с price не отдаём вообще, а не подставляем 0 —
  // фиктивная цена хуже, чем её отсутствие.
  if (hasPrice) offer.price = String(product.price);

  const productJsonLd: Record<string, any> = {
    '@context': 'https://schema.org',
    '@type': 'Product',
    name: product.name,
    description: product.meta_description || product.short_description || '',
    image: imgSrc,
    sku: product.SKU || undefined,
    gtin: product.gtin || undefined,
    mpn: product.mpn || undefined,
    brand: brand ? { '@type': 'Brand', name: brand.name } : undefined,
    offers: offer,
  };
  if (product.rating && Number(product.reviews_count) > 0) {
    productJsonLd.aggregateRating = {
      '@type': 'AggregateRating',
      ratingValue: String(product.rating),
      reviewCount: String(product.reviews_count),
    };
  }

  const faqJsonLd = buildFaqJsonLd(product.faq);

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(productJsonLd) }}
      />
      {faqJsonLd && (
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd) }}
        />
      )}

      <div className="productPage">
        <div className="container">
          <h1 className="productHead__title">{product.seo_h1 || product.name}</h1>

          <Breadcrumbs items={breadcrumbItems} />

          {/* ---- top: gallery + info ---- */}
          <div className="productTop">
            <div className="productGallery">
              {imgSrc ? (
                <Image
                  src={imgSrc}
                  alt={imgAlt}
                  title={product.image_title || undefined}
                  fill
                  sizes="(max-width: 900px) 100vw, 560px"
                  priority
                />
              ) : (
                <span className="productGallery__empty" />
              )}
            </div>

            <div className="productInfo">
              <div className="productInfo__summary">
                <div className="productMeta">
                  {product.SKU && (
                    <span className="productMeta__row">
                      <span className="productMeta__label">{t.product.code}: </span>
                      <span className="productMeta__value">{product.SKU}</span>
                    </span>
                  )}
                  {brand?.name && (
                    <span className="productMeta__row">
                      <span className="productMeta__label">{t.product.manufacturer}: </span>
                      {brand.slug ? (
                        <Link href={`/${locale}/brand/${brand.slug}`} className="productMeta__brand">
                          {brand.name}
                        </Link>
                      ) : (
                        <span className="productMeta__brand">{brand.name}</span>
                      )}
                    </span>
                  )}

                  {hasPrice ? (
                    <div className="productPrice">
                      <span className="productPrice__current">{formatPrice(product.price, currency)}</span>
                      {product.old_price ? (
                        <span className="productPrice__old">{formatPrice(product.old_price, currency)}</span>
                      ) : null}
                    </div>
                  ) : product.price_on_request ? (
                    <div className="productPrice">
                      <span className="productPrice__onRequest">{t.common.requestPrice}</span>
                    </div>
                  ) : null}
                </div>

                <div className="productInfo__action">
                  <ProductInquiry
                    productName={product.name}
                    labels={{
                      requestPrice: t.common.requestPrice,
                      consultTitle: t.product.consultTitle,
                      yourName: t.product.yourName,
                      yourPhone: t.product.yourPhone,
                      yourEmail: t.product.yourEmail,
                      leaveComment: t.product.leaveComment,
                      submit: t.product.submit,
                      sending: t.product.sending,
                      success: t.common.successMessage,
                    }}
                  />
                </div>
              </div>

              {tabs.length > 0 && (
                <>
                  <div className="productInfo__divider" />
                  <ProductTabs tabs={tabs} />
                </>
              )}
            </div>
          </div>

          {/* ---- recommended ---- */}
          {relatedItems.length > 0 && (
            <section className="productRelated">
              <h2 className="productRelated__title">{t.product.recommended}</h2>
              <CatalogItems
                items={relatedItems}
                ctaCategory={t.common.readMore}
                ctaProduct={t.common.readMore}
                skuLabel={t.common.sku}
              />
            </section>
          )}

          <FaqSection items={product.faq} />
        </div>
      </div>
    </>
  );
}
