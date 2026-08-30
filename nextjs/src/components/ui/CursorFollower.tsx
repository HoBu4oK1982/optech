'use client';

/**
 * CursorFollower — портировано «один в один» со структуры freon.kz
 * (src/app/components/CursorFollower.tsx), только цвета — фирменные
 * OPTECH (голубой/оранжевый вместо синего Freon), и поведение при
 * наведении на ссылки упрощено: просто перекрашивается в оранжевый,
 * без увеличения размера и текста внутри.
 *
 * Маленький сплошной кружок 10×10px следует за курсором через
 * gsap.quickTo (buttery-smooth, тот же duration/ease, что у Freon).
 * Никакого градиента/свечения — просто плоский полупрозрачный цвет,
 * как в оригинале.
 */

import { useRef, useEffect } from 'react';
import gsap from 'gsap';

const INTERACTIVE_SELECTOR =
  'a, button, [role="button"], input, textarea, select, .btnRequest, .modalInputBtn, .homeBestBtn, .secCard';

export default function CursorFollower() {
  const outerRef = useRef<HTMLDivElement>(null);
  const visible = useRef(false);
  const hovering = useRef(false);

  useEffect(() => {
    // Пропускаем touch-устройства — там нет настоящего курсора мыши.
    if ('ontouchstart' in window || navigator.maxTouchPoints > 0) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const outer = outerRef.current;
    if (!outer) return;

    const xTo = gsap.quickTo(outer, 'x', { duration: 0.15, ease: 'power2.out' });
    const yTo = gsap.quickTo(outer, 'y', { duration: 0.15, ease: 'power2.out' });

    const onMove = (e: MouseEvent) => {
      xTo(e.clientX);
      yTo(e.clientY);
      if (!visible.current) {
        visible.current = true;
        gsap.to(outer, { opacity: 1, duration: 0.3 });
      }
    };

    const onLeaveWindow = () => {
      visible.current = false;
      gsap.to(outer, { opacity: 0, duration: 0.2 });
    };

    const onEnter = () => {
      if (hovering.current) return;
      hovering.current = true;
      gsap.to(outer, { backgroundColor: 'rgba(255, 159, 28, 0.85)', duration: 0.2 });
    };

    const onLeave = () => {
      if (!hovering.current) return;
      hovering.current = false;
      gsap.to(outer, { backgroundColor: 'rgba(78, 176, 251, 0.5)', duration: 0.2 });
    };

    const onOver = (e: MouseEvent) => {
      const t = e.target as Element;
      if (t?.closest?.(INTERACTIVE_SELECTOR)) onEnter();
    };
    const onOut = (e: MouseEvent) => {
      const related = e.relatedTarget as Element | null;
      if (!related || !related.closest?.(INTERACTIVE_SELECTOR)) onLeave();
    };

    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseleave', onLeaveWindow);
    document.addEventListener('mouseover', onOver);
    document.addEventListener('mouseout', onOut);

    return () => {
      document.removeEventListener('mousemove', onMove);
      document.removeEventListener('mouseleave', onLeaveWindow);
      document.removeEventListener('mouseover', onOver);
      document.removeEventListener('mouseout', onOut);
    };
  }, []);

  return (
    <div
      ref={outerRef}
      aria-hidden="true"
      style={{
        position: 'fixed',
        top: 0,
        left: 0,
        width: 10,
        height: 10,
        borderRadius: '50%',
        backgroundColor: 'rgba(78, 176, 251, 0.5)',
        pointerEvents: 'none',
        zIndex: 9999,
        opacity: 0,
        transform: 'translate(-50%, -50%)',
        willChange: 'transform',
      }}
    />
  );
}
