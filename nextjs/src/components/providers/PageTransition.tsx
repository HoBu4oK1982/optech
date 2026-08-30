'use client';

/**
 * PageTransition v2 — переходы между страницами App Router.
 *
 * Что изменилось относительно v1: контент входит короче и резче (scale+y,
 * ~0.35с вместо 0.5с), линия-скан быстрее, и добавлен «signal ping» —
 * фирменная метка из трёх полосок (тот же мотив, что и «уровень сигнала»
 * в прелоадере), которая коротко пульсирует по центру экрана в момент
 * смены страницы. Это чистый transform/opacity — почти бесплатно по
 * производительности, но даёт узнаваемый «фирменный» акцент на каждом
 * переходе, а не только на первой загрузке.
 *
 * Слои (все fixed/GPU-friendly, ничего тяжёлого для слабых устройств):
 *   1) контент новой страницы — snap-in (scale 0.98→1, y 8px→0, fade);
 *   2) линия-скан один раз проходит по экрану;
 *   3) signal-ping — короткий «удар пульса» по центру (~0.45с);
 *   4) верхний прогресс-бар — стартует по клику на внутреннюю ссылку,
 *      добегает при смене URL.
 */

import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { usePathname } from 'next/navigation';
import gsap from 'gsap';
import './pageTransition.css';

const useIsoLayoutEffect = typeof window !== 'undefined' ? useLayoutEffect : useEffect;

export default function PageTransition({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();

  const contentRef = useRef<HTMLDivElement | null>(null);
  const sweepRef = useRef<HTMLSpanElement | null>(null);
  const barRef = useRef<HTMLSpanElement | null>(null);
  const pingRef = useRef<HTMLDivElement | null>(null);

  const firstRender = useRef(true);
  const reduceRef = useRef(false);
  const barTweenRef = useRef<gsap.core.Tween | null>(null);
  const barState = useRef({ p: 0 });
  const [mounted, setMounted] = useState(false);

  useEffect(() => setMounted(true), []);

  useEffect(() => {
    reduceRef.current = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }, []);

  // ——— верхний прогресс-бар: старт по клику на внутреннюю ссылку ———
  useEffect(() => {
    const startBar = () => {
      const bar = barRef.current;
      if (!bar) return;
      barTweenRef.current?.kill();
      barState.current.p = 0.1;
      bar.style.opacity = '1';
      bar.style.transform = 'scaleX(0.1)';
      barTweenRef.current = gsap.to(barState.current, {
        p: 0.82,
        duration: 1.8,
        ease: 'power1.out',
        onUpdate: () => {
          bar.style.transform = `scaleX(${barState.current.p})`;
        },
      });
    };

    const onClick = (e: MouseEvent) => {
      if (e.defaultPrevented || e.button !== 0) return;
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      const a = (e.target as HTMLElement)?.closest('a');
      if (!a) return;
      const href = a.getAttribute('href');
      const target = a.getAttribute('target');
      if (!href || target === '_blank') return;
      if (href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
      try {
        const url = new URL(href, window.location.href);
        if (url.origin !== window.location.origin) return;
        if (url.pathname === window.location.pathname) return;
      } catch {
        return;
      }
      startBar();
    };

    document.addEventListener('click', onClick, true);

    // programmatic navigation (router.push) — переключатель языка,
    // переход по клику/Enter в поиске и т.п. — не проходит через onClick
    // выше (там нет реального <a> в цели клика), поэтому даём этим местам
    // возможность запустить тот же бар вручную через кастомное событие
    const onCustomStart = () => startBar();
    window.addEventListener('optech:pt-start', onCustomStart);

    return () => {
      document.removeEventListener('click', onClick, true);
      window.removeEventListener('optech:pt-start', onCustomStart);
    };
  }, []);

  // ——— до пейнта: спрятать новый контент, чтобы не мигало ———
  useIsoLayoutEffect(() => {
    if (firstRender.current) return;
    if (reduceRef.current) return;
    if (contentRef.current) {
      gsap.set(contentRef.current, { opacity: 0, y: 8, scale: 0.985 });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [pathname]);

  // ——— после пейнта: проиграть вход ———
  useEffect(() => {
    if (firstRender.current) {
      firstRender.current = false;
      return;
    }

    // плавно бросаем страницу наверх при каждом переходе — используем Lenis
    // (уже поднят и висит на window.lenis в SmoothScroll.tsx), нативный
    // window.scrollTo — запасной вариант, если Lenis почему-то недоступен.
    const lenis = (window as unknown as { lenis?: { scrollTo: (target: number, opts?: Record<string, unknown>) => void } }).lenis;
    if (lenis?.scrollTo) {
      lenis.scrollTo(0, {
        duration: reduceRef.current ? 0 : 0.9,
        easing: (t: number) => 1 - Math.pow(1 - t, 3),
      });
    } else {
      window.scrollTo({ top: 0, behavior: reduceRef.current ? 'auto' : 'smooth' });
    }

    // добежать прогресс-бар
    const bar = barRef.current;
    if (bar) {
      barTweenRef.current?.kill();
      barTweenRef.current = gsap.to(barState.current, {
        p: 1,
        duration: 0.22,
        ease: 'power2.out',
        onUpdate: () => {
          bar.style.transform = `scaleX(${barState.current.p})`;
        },
        onComplete: () => {
          gsap.to(bar, {
            opacity: 0,
            duration: 0.25,
            delay: 0.04,
            onComplete: () => {
              barState.current.p = 0;
              bar.style.transform = 'scaleX(0)';
            },
          });
        },
      });
    }

    if (reduceRef.current) {
      if (contentRef.current) {
        gsap.set(contentRef.current, { opacity: 1, y: 0, scale: 1, clearProps: 'transform,opacity,willChange' });
      }
      return;
    }

    // резкий snap-in контента
    if (contentRef.current) {
      gsap.to(contentRef.current, {
        opacity: 1,
        y: 0,
        scale: 1,
        duration: 0.35,
        ease: 'power3.out',
        onStart: () => {
          // will-change:transform включаем только на время самой анимации —
          // см. подробный комментарий в pageTransition.css про то, почему
          // это НЕЛЬЗЯ держать постоянным CSS-правилом.
          if (contentRef.current) contentRef.current.style.willChange = 'transform, opacity';
        },
        // ВАЖНО: GSAP анимирует y/scale через inline transform, и даже после
        // возврата к y:0/scale:1 этот inline transform остаётся в style
        // (просто с единичной матрицей). Любой непустой transform на предке
        // создаёт новый containing block для position:fixed потомков — из-за
        // этого модалка "Запросить цену" (ProductInquiry, живёт внутри
        // {children} → .pt-content) центрировалась не по экрану, а по всей
        // высоте страницы. clearProps убирает inline-стили после анимации,
        // возвращая .pt-content к «чистому» состоянию без transform.
        clearProps: 'transform,opacity,willChange',
      });
    }

    // линия-скан один раз вниз по экрану
    if (sweepRef.current) {
      gsap.fromTo(
        sweepRef.current,
        { yPercent: -100, opacity: 0 },
        {
          yPercent: 0,
          opacity: 1,
          duration: 0.38,
          ease: 'power2.inOut',
          onStart: () => {
            if (sweepRef.current) sweepRef.current.style.top = '0';
          },
        },
      );
      gsap.to(sweepRef.current, {
        yPercent: 120,
        opacity: 0,
        duration: 0.34,
        delay: 0.2,
        ease: 'power2.in',
      });
    }

    // signal ping — фирменный «удар пульса» по центру экрана
    if (pingRef.current) {
      const bars = pingRef.current.querySelectorAll('.pt-pingBar');
      gsap.killTweensOf([pingRef.current, bars]);
      gsap.set(pingRef.current, { opacity: 1 });
      const tl = gsap.timeline({
        onComplete: () => {
          if (pingRef.current) gsap.set(pingRef.current, { opacity: 0 });
        },
      });
      tl.fromTo(
        bars,
        { scaleY: 0.25, opacity: 0.4 },
        { scaleY: 1, opacity: 1, duration: 0.2, ease: 'power2.out', stagger: 0.05 },
      ).to(bars, { scaleY: 0.25, opacity: 0, duration: 0.22, ease: 'power2.in', stagger: 0.04 }, '+=0.05');
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [pathname]);

  // ——— оверлейные слои (скан, прогресс-бар, signal-ping) вынесены порталом
  // в document.body. Раньше они были соседями .pt-content внутри .pt-wrap,
  // который лежит в .appMain (position:relative; z-index:18 — см. dots.css).
  // .appMain создаёт СОБСТВЕННЫЙ стекинг-контекст, поэтому z-index:9600 у
  // .pt-bar сравнивался снаружи не сам по себе, а как z-index всего .appMain
  // (18) против z-index шапки (25, см. комментарий у `header{}` в styles.css)
  // — и проигрывал: полоса рисовалась ПОД шапкой и была не видна, пока
  // шапка физически перекрывала верх вьюпорта (т.е. почти всегда, кроме
  // случаев, когда страница проскроллена ниже высоты шапки — тогда шапка
  // уже не в вьюпорте и полоса вдруг "появлялась"). Портал в body убирает
  // .pt-bar из стекинг-контекста .appMain, так что её z-index:9600 сравнивается
  // с шапкой (25) напрямую и честно выигрывает.
  const overlay = (
    <>
      <span className="pt-sweep" ref={sweepRef} aria-hidden="true" />
      <span className="pt-bar" ref={barRef} aria-hidden="true" />
      <div className="pt-ping" ref={pingRef} aria-hidden="true">
        <i className="pt-pingBar" />
        <i className="pt-pingBar" />
        <i className="pt-pingBar" />
      </div>
    </>
  );

  return (
    <div className="pt-wrap">
      <div className="pt-content" ref={contentRef}>
        {children}
      </div>
      {mounted && createPortal(overlay, document.body)}
    </div>
  );
}
