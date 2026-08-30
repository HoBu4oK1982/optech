import { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { locales, Locale, getTranslations } from '@/i18n/translations';
import { getSettings, getAllCategories } from '@/lib/api';
import Header from '@/components/layout/Header';
import Footer from '@/components/layout/Footer';
import SmoothScroll from '@/components/providers/SmoothScroll';
import PageTransition from '@/components/providers/PageTransition';
import FloatingNav from '@/components/layout/FloatingNav';
import InnerBg from '@/components/layout/InnerBg';
import { BACKEND_URL } from '@/lib/constants';
import WhatsAppButton from '@/components/ui/WhatsAppButton';
import ScrollTopButton from '@/components/ui/ScrollTopButton';
import CookieConsent from '@/components/ui/CookieConsent';
import CursorFollower from '@/components/ui/CursorFollower';
// catalog.css раньше импортировался отдельно в 11 разных page.tsx
// (catalog, catalog/[category], .../[subcategory], .../[subsubcategory],
// projects, services, solutions, brand/[slug], articles, offers, product) —
// класс .catalogHead/.catalogHead__title общий для всех этих страниц.
// Next.js генерил ОТДЕЛЬНУЮ копию этого CSS в бандл КАЖДОГО роута (отсюда
// куча задублированных .catalogHead в DevTools — по одному на каждый
// посещённый роут). Импорт здесь, в общем layout, даёт один переиспользуемый
// чанк на все страницы сразу.
import '@/components/catalog/catalog.css';

export function generateStaticParams() {
  return locales.map((locale) => ({ locale }));
}

export async function generateMetadata({
  params,
}: {
  params: { locale: string };
}): Promise<Metadata> {
  const t = getTranslations(params.locale);
  return {
    title: {
      default: t.meta.homeTitle,
      template: `%s | OPTECH`,
    },
    description: t.meta.homeDescription,
    openGraph: {
      type: 'website',
      locale: params.locale === 'ru' ? 'ru_RU' : params.locale === 'kz' ? 'kk_KZ' : 'en_US',
      siteName: 'OPTECH',
    },
    alternates: {
      languages: {
        'ru': '/ru',
        'en': '/en',
        'kk': '/kz',
      },
    },
  };
}

export default async function LocaleLayout({
  children,
  params,
}: {
  children: React.ReactNode;
  params: { locale: string };
}) {
  const locale = params.locale as Locale;

  if (!locales.includes(locale)) {
    notFound();
  }

  let settings = null;
  let categories = null;

  try {
    [settings, categories] = await Promise.all([
      getSettings(),
      getAllCategories(locale),
    ]);
  } catch (error) {
    console.error('Failed to fetch layout data:', error);
  }

  return (
    <SmoothScroll>
      <InnerBg />
      <FloatingNav categories={categories} locale={locale} backendUrl={BACKEND_URL} />
      <Header locale={locale} settings={settings} categories={categories} />
      {/* Футер — fixed-under (см. dots.css / styles.css), контент
          "выезжает" при скролле, открывая его снизу, как на freon.kz.
          ВАЖНО: Header и .appMain — плоские соседи, БЕЗ общей обёртки
          (.appShell раньше был именно такой обёрткой и перехватывал
          клики, предназначенные футеру — см. комментарий в dots.css).
          .appMain__sheet — непрозрачный лист с реальным контентом,
          .appFooterSpacer — прозрачная область высотой в футер, которая
          и создаёт эффект выезжающей шторки. На мобильном (≤960px) весь
          этот механизм отключается в CSS — футер там в обычном потоке. */}
      <div className="appMain">
        <div className="appMain__sheet">
          <PageTransition>{children}</PageTransition>
        </div>
        <div className="appFooterSpacer" aria-hidden="true" />
      </div>
      <div className="appBottomDock" aria-hidden="true">
        <div className="appBottomDock__panel" />
      </div>
      <Footer locale={locale} settings={settings} categories={categories} />
      <WhatsAppButton />
      <ScrollTopButton />
      <CookieConsent />
      <CursorFollower />
    </SmoothScroll>
  );
}
