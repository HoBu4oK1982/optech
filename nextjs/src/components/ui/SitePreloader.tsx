'use client';

/**
 * SitePreloader v2 — фирменный прелоадер OPTECH: «захват сигнала».
 *
 * Драматургия (полный цикл ~1.2–1.9с, дальше только если сеть медленная):
 *  1) экран гаснет в фирменную тьму, Canvas плетёт сеть узлов + бегущий
 *     импульс по волоконным линиям;
 *  2) от центра расходятся два кольца-пульса, вспышка — и логотип
 *     «влетает» с лёгким перехлёстом (back-ease) одновременно со сканом;
 *  3) акцентные полосы логотипа дощёлкивают по одной;
 *  4) вместо обычного progress-bar — «уровень сигнала» из 5 полосок,
 *     как индикатор сети на телефоне, зажигаются по нарастающей;
 *  5) как только сайт готов (и прошёл минимальный показ) — вторая вспышка
 *     и шторка уходит вверх со светящейся кромкой.
 *
 * Производительность как и раньше: DPR ≤ 1.5, троттлинг ~30fps, пауза при
 * скрытой вкладке, полное отключение при prefers-reduced-motion, только
 * transform/opacity вне канваса — ничего тяжёлого для слабых устройств.
 * Показывается на каждой полной загрузке (без sessionStorage-пропуска) —
 * так «крутой» вход виден при каждом обновлении страницы.
 */

import { useEffect, useRef, useState } from 'react';
import gsap from 'gsap';

const MIN_SHOW = 1200; // успеть доиграть анимацию входа, но не мигать
const MAX_WAIT = 2800; // страховка на медленной сети

type Node = { x: number; y: number; vx: number; vy: number; r: number; accent: boolean };

export default function SitePreloader() {
  const rootRef = useRef<HTMLDivElement | null>(null);
  const canvasRef = useRef<HTMLCanvasElement | null>(null);
  const logoRef = useRef<HTMLDivElement | null>(null);
  const scanRef = useRef<HTMLSpanElement | null>(null);
  const ring1Ref = useRef<HTMLSpanElement | null>(null);
  const ring2Ref = useRef<HTMLSpanElement | null>(null);
  const flashRef = useRef<HTMLSpanElement | null>(null);
  const pctRef = useRef<HTMLSpanElement | null>(null);
  const barsWrapRef = useRef<HTMLDivElement | null>(null);
  const centerRef = useRef<HTMLDivElement | null>(null);

  const [visible, setVisible] = useState(true);

  useEffect(() => {
    const root = rootRef.current;
    const canvas = canvasRef.current;
    if (!root || !canvas) return;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const prevOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    const started = performance.now();
    let finished = false;
    let rafId = 0;
    const cleanupFns: Array<() => void> = [];

    // ——— Canvas: сеть узлов + волоконные импульсы ———
    const ctx = canvas.getContext('2d', { alpha: true });
    let width = 0;
    let height = 0;
    let dpr = 1;
    let nodes: Node[] = [];
    let running = !reduceMotion && !!ctx;
    let lastFrame = 0;

    const rand = (a: number, b: number) => a + Math.random() * (b - a);

    const resize = () => {
      const rect = root.getBoundingClientRect();
      width = Math.max(320, Math.floor(rect.width));
      height = Math.max(360, Math.floor(rect.height));
      dpr = Math.min(window.devicePixelRatio || 1, 1.5);
      canvas.width = Math.floor(width * dpr);
      canvas.height = Math.floor(height * dpr);
      if (ctx) ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

      const count = width < 720 ? 26 : 38;
      nodes = Array.from({ length: count }, () => ({
        x: rand(0, width),
        y: rand(0, height),
        vx: rand(-0.14, 0.14),
        vy: rand(-0.1, 0.1),
        r: rand(1.1, 2.4),
        accent: Math.random() < 0.16,
      }));
    };

    const fibers = [0, 1, 2];

    const draw = (time: number) => {
      if (!ctx) return;
      const bg = ctx.createLinearGradient(0, 0, width, height);
      bg.addColorStop(0, '#05152b');
      bg.addColorStop(0.55, '#082c5a');
      bg.addColorStop(1, '#0a3f80');
      ctx.fillStyle = bg;
      ctx.fillRect(0, 0, width, height);

      const cy = height * 0.5;
      fibers.forEach((_, i) => {
        const y = cy + (i - 1) * Math.max(56, height * 0.11);
        ctx.save();
        ctx.globalAlpha = i === 1 ? 0.5 : 0.24;
        ctx.strokeStyle = i === 1 ? 'rgba(78,176,251,0.9)' : 'rgba(255,255,255,0.5)';
        ctx.lineWidth = i === 1 ? 1.6 : 1;
        ctx.beginPath();
        ctx.moveTo(-40, y);
        ctx.bezierCurveTo(width * 0.28, y - 60, width * 0.46, y + 64, width * 0.7, y - 16);
        ctx.bezierCurveTo(width * 0.85, y - 70, width * 0.95, y + 40, width + 40, y - 10);
        ctx.stroke();
        ctx.restore();
      });

      const pulseX = ((time * 0.13) % (width + 200)) - 100;
      const pulseY = cy + Math.sin(time * 0.0016) * 26;
      const glow = ctx.createRadialGradient(pulseX, pulseY, 0, pulseX, pulseY, 84);
      glow.addColorStop(0, 'rgba(120,190,255,0.6)');
      glow.addColorStop(0.4, 'rgba(78,176,251,0.18)');
      glow.addColorStop(1, 'rgba(78,176,251,0)');
      ctx.fillStyle = glow;
      ctx.beginPath();
      ctx.arc(pulseX, pulseY, 84, 0, Math.PI * 2);
      ctx.fill();

      for (let i = 0; i < nodes.length; i++) {
        const n = nodes[i];
        if (running) {
          n.x += n.vx;
          n.y += n.vy;
          if (n.x < -20) n.x = width + 20;
          if (n.x > width + 20) n.x = -20;
          if (n.y < -20) n.y = height + 20;
          if (n.y > height + 20) n.y = -20;
        }
        const tw = 0.55 + Math.sin(time * 0.002 + i) * 0.45;
        ctx.globalAlpha = 0.28 + tw * 0.4;
        ctx.fillStyle = n.accent ? '#ff9f1c' : '#4eb0fb';
        ctx.beginPath();
        ctx.arc(n.x, n.y, n.r + tw * 0.7, 0, Math.PI * 2);
        ctx.fill();
      }

      ctx.strokeStyle = 'rgba(78,176,251,0.5)';
      ctx.lineWidth = 1;
      for (let i = 0; i < nodes.length; i++) {
        for (let j = i + 1; j < nodes.length; j++) {
          const a = nodes[i];
          const b = nodes[j];
          const dx = a.x - b.x;
          const dy = a.y - b.y;
          const dist = Math.sqrt(dx * dx + dy * dy);
          if (dist < 118) {
            ctx.globalAlpha = (1 - dist / 118) * 0.22;
            ctx.beginPath();
            ctx.moveTo(a.x, a.y);
            ctx.lineTo(b.x, b.y);
            ctx.stroke();
          }
        }
      }
      ctx.globalAlpha = 1;
    };

    const tick = (time: number) => {
      if (!running) return;
      rafId = requestAnimationFrame(tick);
      if (time - lastFrame < 33) return; // ~30fps
      lastFrame = time;
      draw(time);
    };

    resize();
    if (ctx) draw(0);
    if (running) rafId = requestAnimationFrame(tick);

    const onResize = () => resize();
    window.addEventListener('resize', onResize, { passive: true });
    cleanupFns.push(() => window.removeEventListener('resize', onResize));

    const onVis = () => {
      running = !document.hidden && !reduceMotion && !finished && !!ctx;
      if (running) {
        lastFrame = 0;
        rafId = requestAnimationFrame(tick);
      }
    };
    document.addEventListener('visibilitychange', onVis);
    cleanupFns.push(() => document.removeEventListener('visibilitychange', onVis));

    // ——— «уровень сигнала» вместо обычного progress-bar ———
    const bars = barsWrapRef.current
      ? Array.from(barsWrapRef.current.querySelectorAll<HTMLElement>('.opl-sigBar'))
      : [];

    const progress = { p: 0 };
    const setProgress = () => {
      const val = Math.round(progress.p * 100);
      const lit = Math.round(progress.p * bars.length);
      bars.forEach((b, i) => b.classList.toggle('is-lit', i < lit));
      if (pctRef.current) pctRef.current.textContent = String(val);
    };

    if (reduceMotion) {
      if (logoRef.current) logoRef.current.style.clipPath = 'inset(0 0 0 0)';
      progress.p = 1;
      setProgress();
    } else {
      const introTl = gsap.timeline();
      introTl
        .fromTo(
          centerRef.current,
          { opacity: 0, y: 10 },
          { opacity: 1, y: 0, duration: 0.35, ease: 'power2.out' },
        )
        .fromTo(
          [ring1Ref.current, ring2Ref.current],
          { scale: 0.4, opacity: 0.75 },
          { scale: 1.7, opacity: 0, duration: 0.9, ease: 'power2.out', stagger: 0.16 },
          '<',
        )
        .fromTo(flashRef.current, { opacity: 0 }, { opacity: 1, duration: 0.14, ease: 'power1.out' }, '-=0.55')
        .to(flashRef.current, { opacity: 0, duration: 0.35, ease: 'power1.in' }, '-=0.02')
        .fromTo(
          logoRef.current,
          { clipPath: 'inset(0 100% 0 0)', scale: 1.08 },
          { clipPath: 'inset(0 0% 0 0)', scale: 1, duration: 0.65, ease: 'back.out(1.5)' },
          '-=0.65',
        )
        .fromTo(
          scanRef.current,
          { left: '-6%', opacity: 0 },
          { left: '104%', opacity: 1, duration: 0.65, ease: 'power2.inOut' },
          '<',
        )
        .to(scanRef.current, { opacity: 0, duration: 0.15 }, '-=0.12')
        .fromTo(
          '.opl-logo .op-bar',
          { scaleX: 0, transformOrigin: 'left center' },
          { scaleX: 1, duration: 0.32, ease: 'power3.out', stagger: 0.07 },
          '-=0.32',
        );
      cleanupFns.push(() => introTl.kill());

      const progTl = gsap.to(progress, {
        p: 0.88,
        duration: 1.05,
        ease: 'power1.out',
        delay: 0.15,
        onUpdate: setProgress,
      });
      cleanupFns.push(() => progTl.kill());
    }

    // ——— Уход ———
    const exit = () => {
      if (finished) return;
      finished = true;
      running = false;
      cancelAnimationFrame(rafId);

      const done = () => {
        document.body.style.overflow = prevOverflow;
        setVisible(false);
      };

      if (reduceMotion) {
        gsap.to(root, { opacity: 0, duration: 0.3, ease: 'power1.out', onComplete: done });
        return;
      }

      const tl = gsap.timeline({ onComplete: done });
      tl.to(progress, { p: 1, duration: 0.22, ease: 'power2.out', onUpdate: setProgress })
        .fromTo(flashRef.current, { opacity: 0 }, { opacity: 1, duration: 0.12, ease: 'power1.out' }, '-=0.05')
        .to(flashRef.current, { opacity: 0, duration: 0.3, ease: 'power1.in' }, '-=0.02')
        .to(logoRef.current, { scale: 1.05, duration: 0.25, ease: 'power2.out' }, '-=0.3')
        .to(centerRef.current, { y: -18, opacity: 0, duration: 0.3, ease: 'power2.in' }, '-=0.12')
        .to(root, { yPercent: -101, duration: 0.6, ease: 'expo.inOut' }, '-=0.18');
    };

    let finishTimer = 0;
    const scheduleFinish = () => {
      const elapsed = performance.now() - started;
      const wait = Math.max(0, MIN_SHOW - elapsed);
      finishTimer = window.setTimeout(exit, wait);
    };
    if (document.readyState === 'complete') {
      scheduleFinish();
    } else {
      const onLoad = () => scheduleFinish();
      window.addEventListener('load', onLoad, { once: true });
      cleanupFns.push(() => window.removeEventListener('load', onLoad));
    }
    const hardCap = window.setTimeout(exit, MAX_WAIT);
    cleanupFns.push(() => {
      window.clearTimeout(finishTimer);
      window.clearTimeout(hardCap);
    });

    return () => {
      running = false;
      cancelAnimationFrame(rafId);
      document.body.style.overflow = prevOverflow;
      cleanupFns.forEach((fn) => fn());
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  if (!visible) return null;

  return (
    <div className="opl-root" ref={rootRef} role="status" aria-live="polite" aria-label="Загрузка OPTECH">
      <canvas className="opl-canvas" ref={canvasRef} aria-hidden="true" />
      <div className="opl-vignette" aria-hidden="true" />

      <div className="opl-center" ref={centerRef}>
        <div className="opl-logoWrap">
          <span className="opl-ring" ref={ring1Ref} aria-hidden="true" />
          <span className="opl-ring" ref={ring2Ref} aria-hidden="true" />
          <span className="opl-flash" ref={flashRef} aria-hidden="true" />

          <div className="opl-logo" ref={logoRef}>
            <svg viewBox="0 0 824.13 161.34" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <g transform="translate(-441.87 -337.19)">
                <path className="cls-1" d="M522.32,498.53A80.12,80.12,0,0,0,603,417.86c0-45.43-35.9-80.67-80.67-80.67s-80.45,35.24-80.45,80.67C441.87,463.07,477.55,498.53,522.32,498.53Zm0-29.92c-28.15,0-50.09-21.05-50.09-50.75,0-29.92,21.94-51,50.09-51s50.08,21.05,50.08,51C572.4,447.56,550.46,468.61,522.32,468.61Z" />
                <path className="cls-1" d="M682.31,340.3H624.47V495.43h30.58v-51h27.26c29.92,0,53-23,53-52.08S712.23,340.3,682.31,340.3Zm0,75.57H655.05v-47h27.26c13.07,0,22.6,10,22.6,23.5C704.91,405.67,695.38,415.87,682.31,415.87Z" />
                <path className="cls-1" d="M854.94,340.3H740.81v29.25h41.66V495.43h30.58V369.55h41.89Z" />
                <path className="cls-1" d="M1061.91,498.53c28.58,0,53.63-14.41,66.7-36.57l-26.37-15.29c-7.31,13.52-22.6,21.94-40.33,21.94-30.37,0-50.31-21.05-50.31-50.75,0-29.92,19.94-51,50.31-51,17.73,0,32.8,8.42,40.33,22.16l26.37-15.29c-13.29-22.16-38.34-36.57-66.7-36.57-47,0-80.67,35.24-80.67,80.67C981.24,463.07,1014.92,498.53,1061.91,498.53Z" />
                <path className="cls-1" d="M1235.64,340.3v61.61H1178V340.3h-30.58V495.43H1178V431.16h57.62v64.27H1266V340.3Z" />
                <path className="cls-2 op-bar" d="M874.47,340.22H941.2v29.52H874.47Z" />
                <path className="cls-2 op-bar" d="M874.47,401.82H956.6v29.52H874.47Z" />
                <path className="cls-2 op-bar" d="M874.47,466h96.25V495.5H874.47Z" />
              </g>
            </svg>
          </div>
          <span className="opl-scan" ref={scanRef} aria-hidden="true" />
        </div>

        <div className="opl-meta">
          <div className="opl-sigMeter" ref={barsWrapRef} aria-hidden="true">
            <i className="opl-sigBar" />
            <i className="opl-sigBar" />
            <i className="opl-sigBar" />
            <i className="opl-sigBar" />
            <i className="opl-sigBar" />
          </div>
          <div className="opl-pct">
            <span ref={pctRef}>0</span>%
          </div>
        </div>

        <div className="opl-tag">Оптические технологии</div>
      </div>

      <span className="opl-edge" aria-hidden="true" />
    </div>
  );
}
