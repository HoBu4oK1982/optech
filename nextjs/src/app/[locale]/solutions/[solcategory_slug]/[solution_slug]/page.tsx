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
import { getTranslations } from '@/i18n/translations';
import { getSolution } from '@/lib/api';
import { storageUrl, absolutizeRichContent } from '@/lib/utils';
import { buildMetadata, buildFaqJsonLd } from '@/lib/seo';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import FaqSection from '@/components/seo/FaqSection';
import { notFound } from 'next/navigation';

export async function generateMetadata({ params }: { params: { locale: string; solcategory_slug: string; solution_slug: string } }): Promise<Metadata> {
  const data = await getSolution(params.solcategory_slug, params.solution_slug, params.locale);
  if (!data?.solution) return { title: 'Not Found' };
  const s = data.solution;
  return buildMetadata({
    entity: s,
    fallbackTitle: s.title,
    ogImage: s.og_image ? storageUrl(s.og_image) : s.image ? storageUrl(s.image) : null,
    locale: params.locale,
    path: `/solutions/${params.solcategory_slug}/${params.solution_slug}`,
  });
}

export default async function SolutionDetailPage({ params }: { params: { locale: string; solcategory_slug: string; solution_slug: string } }) {
  const { locale, solcategory_slug, solution_slug } = params;
  const t = getTranslations(locale);
  const data = await getSolution(solcategory_slug, solution_slug, locale);
  if (!data?.solution) notFound();
  const s = data.solution;

  const faqJsonLd = buildFaqJsonLd(s.faq);

  return (
    <div className="max-w-7xl mx-auto px-4 py-6">
      {faqJsonLd && (
        <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd) }} />
      )}
      <Breadcrumbs items={[
        { label: t.nav.home, href: `/${locale}` },
        { label: t.nav.solutions, href: `/${locale}/solutions` },
        { label: data.solcategory, href: `/${locale}/solutions/${solcategory_slug}` },
        { label: s.title },
      ]} />
      <article className="max-w-4xl">
        <h1 className="text-3xl font-bold text-dark mb-6">{s.seo_h1 || s.title}</h1>
        {s.image && (
          <div className="relative mb-8 aspect-[16/9] max-h-[500px] overflow-hidden rounded-xl">
            <Image
              src={storageUrl(s.image)}
              alt={s.image_alt || s.title}
              title={s.image_title || undefined}
              fill
              sizes="(max-width: 1024px) 100vw, 896px"
              className="object-cover"
              priority
            />
          </div>
        )}
        {s.description && (
          <div className="rich-content text-gray-700 leading-relaxed" dangerouslySetInnerHTML={{ __html: absolutizeRichContent(s.description) }} />
        )}
      </article>

      <FaqSection items={s.faq} />
    </div>
  );
}
