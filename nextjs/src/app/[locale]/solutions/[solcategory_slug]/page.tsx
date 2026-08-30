export const dynamic = "force-dynamic";
import { Metadata } from 'next';
import Link from 'next/link';
import { getTranslations } from '@/i18n/translations';
import { getSolCategory } from '@/lib/api';
import { storageUrl, truncateHtml } from '@/lib/utils';
import { buildMetadata, buildFaqJsonLd } from '@/lib/seo';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import FaqSection from '@/components/seo/FaqSection';
import { notFound } from 'next/navigation';

export async function generateMetadata({ params }: { params: { locale: string; solcategory_slug: string } }): Promise<Metadata> {
  const data = await getSolCategory(params.solcategory_slug, params.locale);
  const cat = data?.solcategory_data;
  if (!cat) return { title: data?.solcategory || 'Solutions' };
  return buildMetadata({
    entity: cat,
    fallbackTitle: cat.title || data.solcategory,
    ogImage: cat.og_image ? storageUrl(cat.og_image) : cat.image ? storageUrl(cat.image) : null,
    canonicalPath: `/${params.locale}/solutions/${params.solcategory_slug}`,
  });
}

export default async function SolCategoryPage({ params }: { params: { locale: string; solcategory_slug: string } }) {
  const { locale, solcategory_slug } = params;
  const t = getTranslations(locale);
  const data = await getSolCategory(solcategory_slug, locale);
  if (!data) notFound();
  const cat = data.solcategory_data;

  const faqJsonLd = buildFaqJsonLd(cat?.faq);

  return (
    <div className="max-w-7xl mx-auto px-4 py-6">
      {faqJsonLd && (
        <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd) }} />
      )}
      <Breadcrumbs items={[
        { label: t.nav.home, href: `/${locale}` },
        { label: t.nav.solutions, href: `/${locale}/solutions` },
        { label: data.solcategory },
      ]} />
      <h1 className="text-3xl font-bold text-dark mb-8">{cat?.seo_h1 || data.solcategory}</h1>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {data.solutions?.map((sol: any) => (
          <Link key={sol.id} href={`/${locale}/solutions/${solcategory_slug}/${sol.slug}`}
            className="bg-white rounded-xl border hover:shadow-lg transition-all overflow-hidden group">
            {sol.image && (
              <div className="w-full aspect-video overflow-hidden">
                <img src={storageUrl(sol.image)} alt={sol.image_alt || sol.title} className="object-cover w-full h-full group-hover:scale-105 transition-transform duration-300" loading="lazy" />
              </div>
            )}
            <div className="p-5">
              <h2 className="font-semibold text-dark group-hover:text-primary transition-colors mb-2">{sol.title}</h2>
              {sol.description && <p className="text-sm text-gray-500 line-clamp-3">{truncateHtml(sol.description)}</p>}
            </div>
          </Link>
        ))}
      </div>

      <FaqSection items={cat?.faq} />
    </div>
  );
}
