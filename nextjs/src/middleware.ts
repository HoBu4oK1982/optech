import { NextRequest, NextResponse } from 'next/server';
import { locales, defaultLocale } from '@/i18n/translations';

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // Skip middleware for static files, api routes, etc.
  if (
    pathname.startsWith('/_next') ||
    pathname.startsWith('/api') ||
    pathname.startsWith('/images') ||
    pathname.includes('.') // static files
  ) {
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
  matcher: ['/((?!_next|api|images|favicon.ico).*)'],
};
