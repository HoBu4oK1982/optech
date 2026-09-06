// ISR: статическая страница, пересборка не чаще раза в час.
export const revalidate = 3600;

import { Metadata } from 'next';
import { buildMetadata } from '@/lib/seo';
import { getTranslations } from '@/i18n/translations';
import { makeT } from '@/i18n/dict';
import { getLicenses } from '@/lib/api';
import { BACKEND_URL } from '@/lib/constants';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import LicenseGrid from '@/components/info/LicenseGrid';
import '@/components/info/infoPages.css';

export async function generateMetadata({ params }: { params: { locale: string } }): Promise<Metadata> {
  const tt = makeT(params.locale);
  return buildMetadata({
    locale: params.locale,
    path: '/license',
    fallbackTitle: tt('licence_page_title'),
    fallbackDescription: getTranslations(params.locale).meta.licenseDescription,
  });
}

export default async function LicensePage({ params }: { params: { locale: string } }) {
  const { locale } = params;
  const t = getTranslations(locale);
  const tt = makeT(locale);
  const licenses = await getLicenses().catch(() => []);

  const items = (licenses || []).map((l: any) => ({
    id: l.id,
    src: `${BACKEND_URL}/assets/images/licenses/${l.image}`,
    alt: l.alt || l.type || '',
  }));

  const emptyText =
    locale === 'en'
      ? 'No certificates here yet.'
      : locale === 'kz'
      ? 'Бұл бөлімде әзірше сертификаттар жоқ.'
      : 'Сертификаты пока не добавлены.';

  return (
    <div className="infoPage">
      <div className="container">
        <Breadcrumbs items={[{ label: t.nav.home, href: `/${locale}` }, { label: tt('licence_page_title') }]} />
        <div className="catalogHead">
          <h1 className="catalogHead__title">{tt('licence_page_title')}</h1>
        </div>

        {items.length > 0 ? (
          <LicenseGrid items={items} />
        ) : (
          <div className="catalogEmpty">{emptyText}</div>
        )}
      </div>
    </div>
  );
}
