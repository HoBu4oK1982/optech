export const dynamic = "force-dynamic";
import { Metadata } from 'next';
import { getTranslations } from '@/i18n/translations';
import { getProject } from '@/lib/api';
import { storageUrl, absolutizeRichContent } from '@/lib/utils';
import { buildMetadata, buildFaqJsonLd } from '@/lib/seo';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import FaqSection from '@/components/seo/FaqSection';
import { notFound } from 'next/navigation';

export async function generateMetadata({ params }: { params: { locale: string; project_slug: string } }): Promise<Metadata> {
  const item = await getProject(params.project_slug, params.locale);
  if (!item) return { title: 'Not Found' };
  return buildMetadata({
    entity: item,
    fallbackTitle: item.title,
    ogImage: item.og_image ? storageUrl(item.og_image) : item.image ? storageUrl(item.image) : null,
    canonicalPath: `/${params.locale}/project/${params.project_slug}`,
  });
}

export default async function ProjectDetailPage({ params }: { params: { locale: string; project_slug: string } }) {
  const { locale, project_slug } = params;
  const t = getTranslations(locale);
  const item = await getProject(project_slug, locale);
  if (!item) notFound();

  const facts: { label: string; value: string }[] = [];
  if (item.client) facts.push({ label: 'Клиент', value: item.client });
  if (item.project_year) facts.push({ label: 'Год', value: item.project_year });
  if (item.location) facts.push({ label: 'Локация', value: item.location });

  const metrics: { label?: string; value?: string }[] = Array.isArray(item.result_metrics)
    ? item.result_metrics.filter((m: any) => m?.label || m?.value)
    : [];

  const faqJsonLd = buildFaqJsonLd(item.faq);

  return (
    <div className="max-w-7xl mx-auto px-4 py-6">
      {faqJsonLd && (
        <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd) }} />
      )}
      <Breadcrumbs items={[{ label: t.nav.home, href: `/${locale}` }, { label: t.nav.projects, href: `/${locale}/projects` }, { label: item.title }]} />
      <article className="max-w-4xl">
        <h1 className="text-3xl font-bold text-dark mb-6">{item.seo_h1 || item.title}</h1>

        {(facts.length > 0 || metrics.length > 0) && (
          <div className="mb-8 flex flex-wrap gap-x-8 gap-y-3 text-sm">
            {facts.map((f) => (
              <span key={f.label}>
                <span className="text-gray-400">{f.label}: </span>
                <span className="font-semibold text-dark">{f.value}</span>
              </span>
            ))}
            {metrics.map((m, i) => (
              <span key={i}>
                <span className="font-semibold text-primary">{m.value}</span>{' '}
                <span className="text-gray-400">{m.label}</span>
              </span>
            ))}
          </div>
        )}

        {item.image && (
          <div className="mb-8 rounded-xl overflow-hidden">
            <img
              src={storageUrl(item.image)}
              alt={item.image_alt || item.title}
              title={item.image_title || undefined}
              className="w-full object-cover max-h-[500px]"
            />
          </div>
        )}
        {item.description && <div className="rich-content text-gray-700 leading-relaxed" dangerouslySetInnerHTML={{ __html: absolutizeRichContent(item.description) }} />}
      </article>

      <FaqSection items={item.faq} />
    </div>
  );
}
