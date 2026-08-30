export const dynamic = "force-dynamic";
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
    canonicalPath: `/${params.locale}/solutions/${params.solcategory_slug}/${params.solution_slug}`,
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
          <div className="mb-8 rounded-xl overflow-hidden">
            <img
              src={storageUrl(s.image)}
              alt={s.image_alt || s.title}
              title={s.image_title || undefined}
              className="w-full object-cover max-h-[500px]"
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
