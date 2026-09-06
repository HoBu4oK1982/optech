// ISR: контентный раздел, пересборка не чаще раза в 10 минут
// (см. комментарий про force-dynamic в app/[locale]/page.tsx).
export const revalidate = 600;
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

import Image from 'next/image';
import { Metadata } from 'next';
import { buildMetadata } from '@/lib/seo';
import { getTranslations } from '@/i18n/translations';
import { getService } from '@/lib/api';
import { storageUrl, absolutizeRichContent } from '@/lib/utils';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import FaqSection from '@/components/seo/FaqSection';
import { notFound } from 'next/navigation';

export async function generateMetadata({ params }: { params: { locale: string; service_slug: string } }): Promise<Metadata> {
  const item = await getService(params.service_slug, params.locale);
  if (!item) return { title: 'Not Found' };
  return buildMetadata({
    entity: item,
    locale: params.locale,
    path: `/service/${params.service_slug}`,
    fallbackTitle: item.title,
    ogImage: item.og_image ? storageUrl(item.og_image) : item.image ? storageUrl(item.image) : null,
  });
}

export default async function ServiceDetailPage({ params }: { params: { locale: string; service_slug: string } }) {
  const { locale, service_slug } = params;
  const t = getTranslations(locale);
  const item = await getService(service_slug, locale);
  if (!item) notFound();
  return (
    <div className="max-w-7xl mx-auto px-4 py-6">
      <Breadcrumbs items={[{ label: t.nav.home, href: `/${locale}` }, { label: t.nav.services, href: `/${locale}/services` }, { label: item.title }]} />
      <article className="max-w-4xl">
        <h1 className="text-3xl font-bold text-dark mb-6">{item.seo_h1 || item.title}</h1>
        {item.image && (
          <div className="relative mb-8 aspect-[16/9] max-h-[500px] overflow-hidden rounded-xl">
            <Image
              src={storageUrl(item.image)}
              alt={item.image_alt || item.title}
              title={item.image_title || undefined}
              fill
              sizes="(max-width: 1024px) 100vw, 896px"
              className="object-cover"
              priority
            />
          </div>
        )}
        {item.description && <div className="rich-content text-gray-700 leading-relaxed" dangerouslySetInnerHTML={{ __html: absolutizeRichContent(item.description) }} />}
        <FaqSection items={item.faq} />
      </article>
    </div>
  );
}
