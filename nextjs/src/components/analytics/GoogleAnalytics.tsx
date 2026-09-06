'use client';

import Script from 'next/script';
import { usePathname, useSearchParams } from 'next/navigation';
import { useEffect, useRef, Suspense } from 'react';

declare global {
  interface Window {
    dataLayer?: any[];
    gtag?: (...args: any[]) => void;
  }
}

/**
 * GA4. Поле ga4_id есть в настройках админки с самого начала, но на фронте
 * не читалось — Google Analytics на сайте не стояло вообще, вся аналитика
 * держалась на одной Яндекс.Метрике.
 *
 * Как и у Метрики, App Router не перезагружает документ при переходах,
 * поэтому просмотры после первой загрузки нужно отправлять вручную.
 */
function GaPageViews({ id }: { id: string }) {
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const isFirstRender = useRef(true);

  useEffect(() => {
    if (isFirstRender.current) {
      isFirstRender.current = false;
      return;
    }
    if (typeof window.gtag === 'function') {
      const qs = searchParams?.toString();
      window.gtag('event', 'page_view', {
        page_path: pathname + (qs ? `?${qs}` : ''),
        page_location: window.location.href,
      });
    }
  }, [id, pathname, searchParams]);

  return null;
}

export default function GoogleAnalytics({ id }: { id?: string | null }) {
  if (!id) return null;

  return (
    <>
      <Script src={`https://www.googletagmanager.com/gtag/js?id=${id}`} strategy="afterInteractive" />
      <Script id="ga4-init" strategy="afterInteractive">
        {`
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          window.gtag = gtag;
          gtag('js', new Date());
          gtag('config', '${id}', { send_page_view: true });
        `}
      </Script>
      <Suspense fallback={null}>
        <GaPageViews id={id} />
      </Suspense>
    </>
  );
}
