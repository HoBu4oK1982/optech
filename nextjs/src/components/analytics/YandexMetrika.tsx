'use client';

import Script from 'next/script';
import { usePathname, useSearchParams } from 'next/navigation';
import { useEffect, useRef, Suspense } from 'react';

const METRIKA_ID = 110481428;

declare global {
  interface Window {
    ym?: (...args: any[]) => void;
  }
}

// Первую загрузку страницы Метрика уже учитывает сама через init() ниже.
// Этот компонент шлёт ym(...,'hit',...) на КАЖДЫЙ последующий клиентский
// переход (Next.js App Router не перезагружает документ при навигации,
// поэтому без явного hit Метрика такие переходы просто не увидит).
function MetrikaHitTracker() {
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const isFirstRender = useRef(true);

  useEffect(() => {
    if (isFirstRender.current) {
      isFirstRender.current = false;
      return;
    }
    if (typeof window.ym === 'function') {
      const qs = searchParams?.toString();
      const url = window.location.origin + pathname + (qs ? `?${qs}` : '');
      window.ym(METRIKA_ID, 'hit', url, { referer: document.referrer });
    }
  }, [pathname, searchParams]);

  return null;
}

export default function YandexMetrika() {
  return (
    <>
      <Script id="yandex-metrika" strategy="afterInteractive">
        {`
          (function(m,e,t,r,i,k,a){
              m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
              m[i].l=1*new Date();
              for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
              k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
          })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=${METRIKA_ID}', 'ym');
          ym(${METRIKA_ID}, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
        `}
      </Script>
      <noscript>
        <div>
          <img src={`https://mc.yandex.ru/watch/${METRIKA_ID}`} style={{ position: 'absolute', left: '-9999px' }} alt="" />
        </div>
      </noscript>
      <Suspense fallback={null}>
        <MetrikaHitTracker />
      </Suspense>
    </>
  );
}
