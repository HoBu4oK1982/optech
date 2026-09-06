import { NextRequest, NextResponse } from 'next/server';
import { locales, defaultLocale } from '@/i18n/translations';

// Пути без префикса локали: служебные каталоги Next, статика из public/
// и файлы, которые отдают отдельные роуты (robots.txt, sitemap.xml).
const SKIP_PREFIXES = ['/_next', '/api', '/assets', '/images', '/sitemap'];
const SKIP_FILES = new Set(['/robots.txt', '/sitemap.xml', '/favicon.ico', '/site.webmanifest', '/manifest.webmanifest']);

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // Раньше здесь стояло `pathname.includes('.')` — под это правило попадал
  // ЛЮБОЙ путь с точкой, а не только реальная статика. Такой путь уходил в
  // приложение с [locale] вида "nope.txt", layout бросал notFound(), и вместо
  // фирменной 404 отдавалась пустая страница. Теперь пропускаем только
  // настоящие статические префиксы и файлы, остальное идёт обычным путём и
  // получает нормальную 404.
  if (SKIP_PREFIXES.some((prefix) => pathname.startsWith(prefix)) || SKIP_FILES.has(pathname)) {
    return NextResponse.next();
  }

  // Check if pathname already has a locale
  const pathnameHasLocale = locales.some(
    (locale) => pathname.startsWith(`/${locale}/`) || pathname === `/${locale}`
  );

  if (pathnameHasLocale) {
    return NextResponse.next();
  }

  // Detect locale from Accept-Language header or default
  const acceptLanguage = request.headers.get('accept-language') || '';
  let detectedLocale = defaultLocale;

  for (const locale of locales) {
    if (acceptLanguage.includes(locale)) {
      detectedLocale = locale;
      break;
    }
  }

  // Redirect to locale-prefixed path.
  // 301 (не дефолтный 307) — это подтверждённый постоянный переезд структуры
  // URL (/catalog/x -> /ru/catalog/x), а не персонализированный редирект:
  // боты поисковиков не шлют вариативный Accept-Language и стабильно
  // получают один и тот же locale по умолчанию на каждый повторный обход.
  const newUrl = new URL(`/${detectedLocale}${pathname}`, request.url);
  return NextResponse.redirect(newUrl, 301);
}

export const config = {
  matcher: ['/((?!_next|api|assets|images|favicon.ico|robots.txt|sitemap|site.webmanifest).*)'],
};
