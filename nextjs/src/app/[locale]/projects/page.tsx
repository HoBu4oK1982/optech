// ISR: контентный раздел, пересборка не чаще раза в 10 минут
// (см. комментарий про force-dynamic в app/[locale]/page.tsx).
export const revalidate = 600;

import { Metadata } from 'next';
import { buildMetadata } from '@/lib/seo';
import { getTranslations } from '@/i18n/translations';
import { getProjects } from '@/lib/api';
import { truncateHtml } from '@/lib/utils';
import { BACKEND_URL } from '@/lib/constants';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import SectionGrid, { SectionItem } from '@/components/sections/SectionGrid';
import '@/components/catalog/catalog.css';

export async function generateMetadata({ params }: { params: { locale: string } }): Promise<Metadata> {
  const t = getTranslations(params.locale);
  return buildMetadata({
    locale: params.locale,
    path: '/projects',
    fallbackTitle: t.nav.projects,
    fallbackDescription: t.meta.projectsDescription,
  });
}

export default async function ProjectsPage({ params }: { params: { locale: string } }) {
  const { locale } = params;
  const t = getTranslations(locale);
  const projects = await getProjects(locale).catch(() => []);

  const items: SectionItem[] = (projects || []).map((p: any) => ({
    id: p.id,
    title: p.title,
    href: `/${locale}/project/${p.slug}`,
    imageUrl: p.image ? `${BACKEND_URL}/assets/images/projects/${p.image}` : null,
    imageAlt: p.image_alt,
    imageTitle: p.image_title,
    excerpt: p.description ? truncateHtml(p.description, 120) : null,
  }));

  return (
    <div className="catalogPage">
      <div className="container">
        <Breadcrumbs items={[{ label: t.nav.home, href: `/${locale}` }, { label: t.nav.projects }]} />
        <div className="catalogHead">
          <h1 className="catalogHead__title">{t.nav.projects}</h1>
        </div>
        {items.length > 0 ? (
          <SectionGrid items={items} ctaLabel={t.common.readMore} variant="projects" showCta={false} />
        ) : (
          <div className="catalogEmpty">
            {locale === 'en'
              ? 'No projects here yet.'
              : locale === 'kz'
              ? 'Бұл бөлімде әзірше жобалар жоқ.'
              : 'Пока нет опубликованных проектов.'}
          </div>
        )}
      </div>
    </div>
  );
}
