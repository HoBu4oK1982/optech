export const dynamic = "force-dynamic";
import { Metadata } from 'next';
import { getTranslations } from '@/i18n/translations';
import { getService } from '@/lib/api';
import { storageUrl, absolutizeRichContent } from '@/lib/utils';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import { notFound } from 'next/navigation';

export async function generateMetadata({ params }: { params: { locale: string; service_slug: string } }): Promise<Metadata> {
  const item = await getService(params.service_slug, params.locale);
  if (!item) return { title: 'Not Found' };
  return { title: item.meta_title || item.title, description: item.meta_description || '', keywords: item.meta_keywords || '' };
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
        <h1 className="text-3xl font-bold text-dark mb-6">{item.title}</h1>
        {item.image && <div className="mb-8 rounded-xl overflow-hidden"><img src={storageUrl(item.image)} alt={item.title} className="w-full object-cover max-h-[500px]" /></div>}
        {item.description && <div className="rich-content text-gray-700 leading-relaxed" dangerouslySetInnerHTML={{ __html: absolutizeRichContent(item.description) }} />}
      </article>
    </div>
  );
}
