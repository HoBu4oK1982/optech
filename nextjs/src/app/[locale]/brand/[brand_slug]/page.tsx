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
import { buildMetadata } from '@/lib/seo';
import { notFound } from 'next/navigation';
import { getTranslations } from '@/i18n/translations';
import { getOneBrand } from '@/lib/api';
import { BACKEND_URL } from '@/lib/constants';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import CatalogItems, { CatalogItem } from '@/components/catalog/CatalogItems';
import SeoText from '@/components/seo/SeoText';
import FaqSection from '@/components/seo/FaqSection';

const brandImg = (image?: string | null) =>
  image ? `${BACKEND_URL}/assets/images/brands/${image}` : null;

export async function generateMetadata({
  params,
}: {
  params: { locale: string; brand_slug: string };
}): Promise<Metadata> {
  const data = await getOneBrand(params.brand_slug, params.locale);
  // brand_info — целиком локализованная модель бренда: до этого шага у брендов
  // не было ни canonical, ни robots, ни OG, ни переводов meta_*.
  const brand = data?.brand_info || null;

  return buildMetadata({
    entity: brand || { meta_keywords: data?.brand_keywords },
    locale: params.locale,
    path: `/brand/${params.brand_slug}`,
    fallbackTitle: data?.brand_title || data?.brand_name || 'Brand',
    fallbackDescription: data?.brand_description || '',
    ogImage: brandImg(brand?.og_image || brand?.image),
  });
}

export default async function BrandPage({
  params,
}: {
  params: { locale: string; brand_slug: string };
}) {
  const { locale, brand_slug } = params;
  const t = getTranslations(locale);

  const data = await getOneBrand(brand_slug, locale);
  if (!data) notFound();

  const brand = data.brand_info || {};
  const brandWord = locale === 'en' ? 'Brand' : 'Бренд';

  const items: CatalogItem[] = (data.brand || []).map((p: any) => ({
    id: p.id,
    name: p.name,
    image: p.image,
    imageAlt: p.image_alt,
    imageTitle: p.image_title,
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
            { label: brand.breadcrumb_title || data.brand_name },
          ]}
        />

        <div className="catalogHead">
          <h1 className="catalogHead__title">
            {brand.seo_h1 || `${brandWord} — ${data.brand_name}`}
          </h1>
        </div>

        <SeoText html={brand.seo_text_top} variant="top" />

        <CatalogItems items={items} skuLabel={t.common.sku} />

        <SeoText heading={brand.seo_h2} html={brand.seo_text_bottom} variant="bottom" />

        <FaqSection items={brand.faq} />
      </div>
    </div>
  );
}
