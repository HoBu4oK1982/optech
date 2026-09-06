import { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { locales, Locale, getTranslations } from '@/i18n/translations';
import { htmlLang } from '@/lib/seo';
import { getSettings, getAllCategories } from '@/lib/api';
import Header from '@/components/layout/Header';
import Footer from '@/components/layout/Footer';
import SmoothScroll from '@/components/providers/SmoothScroll';
import PageTransition from '@/components/providers/PageTransition';
import FloatingNav from '@/components/layout/FloatingNav';
import InnerBg from '@/components/layout/InnerBg';
import { BACKEND_URL, SITE_URL } from '@/lib/constants';
import WhatsAppButton from '@/components/ui/WhatsAppButton';
import ScrollTopButton from '@/components/ui/ScrollTopButton';
import CookieConsent from '@/components/ui/CookieConsent';
import CursorFollower from '@/components/ui/CursorFollower';
import SitePreloader from '@/components/ui/SitePreloader';
import YandexMetrika from '@/components/analytics/YandexMetrika';
import GoogleAnalytics from '@/components/analytics/GoogleAnalytics';
import GoogleTagManager from '@/components/analytics/GoogleTagManager';
import SiteJsonLd from '@/components/seo/SiteJsonLd';
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

/**
 * Значение поля настроек с учётом локали: default_meta_title /
 * default_meta_title_en / default_meta_title_kz. Если перевод не заполнен,
 * откатываемся на русский вариант, а не на пустую строку.
 */
function localizedSetting(settings: any, base: string, locale: string): string | null {
  const suffix = locale === 'en' ? '_en' : locale === 'kz' ? '_kz' : '';
  return (suffix && settings?.[base + suffix]) || settings?.[base] || null;
}

export async function generateMetadata({
  params,
}: {
  params: { locale: string };
}): Promise<Metadata> {
  const t = getTranslations(params.locale);

  // Настройки нужны ради дефолтных title/description и кодов подтверждения
  // прав в Search Console / Вебмастере: все эти поля годами заполнялись в
  // админке и никогда не доезжали до фронта. Тот же fetch вызывается ниже
  // в самом layout — Next дедуплицирует одинаковые запросы в рамках рендера.
  const settings = await getSettings().catch(() => null);

  const defaultTitle =
    localizedSetting(settings, 'default_meta_title', params.locale) || t.meta.homeTitle;
  const defaultDescription =
    localizedSetting(settings, 'default_meta_description', params.locale) || t.meta.homeDescription;

  return {
    // metadataBase обязателен, чтобы Next.js разворачивал относительные пути
    // в OG/twitter-разметке в абсолютные URL. Без него og:image и og:url
    // уезжали относительными, и соцсети/боты их не резолвили.
    metadataBase: new URL(SITE_URL),
    title: {
      default: defaultTitle,
      template: `%s | OPTECH`,
    },
    description: defaultDescription,
    verification: {
      google: settings?.google_verification || undefined,
      yandex: settings?.yandex_verification || undefined,
    },
    openGraph: {
      type: 'website',
      locale: params.locale === 'ru' ? 'ru_RU' : params.locale === 'kz' ? 'kk_KZ' : 'en_US',
      siteName: 'OPTECH',
    },
    // alternates здесь НЕТ намеренно: hreflang зависит от пути конкретной
    // страницы и собирается в buildMetadata (см. lib/seo.ts). Раньше в этом
    // месте стоял статический блок { ru: '/ru', en: '/en', kk: '/kz' }, и он
    // по наследованию попадал на все страницы сайта — со страницы товара
    // hreflang вёл на главные страницы локалей.
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
    // <html>/<body> живут здесь, а не в корневом layout: только отсюда виден
    // params.locale, от которого зависит lang (см. app/layout.tsx).
    <html lang={htmlLang(locale)} suppressHydrationWarning>
      <head>
        {/* Полный набор иконок вместо одного favicon.ico 16×16: SVG для
            современных браузеров, PNG-размеры для вкладок и закладок,
            apple-touch-icon для «на экран Домой» в iOS, манифест для Android. */}
        <link rel="icon" href="/assets/images/favicon.svg" type="image/svg+xml" />
        <link rel="icon" href="/assets/images/favicon-32x32.png" sizes="32x32" type="image/png" />
        <link rel="icon" href="/assets/images/favicon-16x16.png" sizes="16x16" type="image/png" />
        <link rel="icon" href="/assets/images/favicon.ico" sizes="any" />
        <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png" sizes="180x180" />
        <link rel="manifest" href="/site.webmanifest" />
        <meta name="theme-color" content="#121123" />
      </head>
      <body>
        <SiteJsonLd settings={settings} locale={locale} />
        <GoogleTagManager id={settings?.gtm_id} />
        <GoogleAnalytics id={settings?.ga4_id} />
        <YandexMetrika id={settings?.yandex_metrika_id} />
        <SitePreloader />
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
      </body>
    </html>
  );
}
