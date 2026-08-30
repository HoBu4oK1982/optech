'use client';

import { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import Link from 'next/link';
import gsap from 'gsap';
import './mobileNavDrawer.css';

type NavItem = { href: string; label: string; icon?: 'home' };

/**
 * Второе мобильное меню («Меню» справа) — разделы сайта (Бренды, Проекты,
 * Новости...). Раньше было на CSS-transition (right: -500px -> 0, 1s) и
 * ссылки не закрывали панель при переходе — после клика по ссылке страница
 * менялась, а меню так и оставалось открытым поверх неё. Теперь: портал +
 * GSAP (тот же паттерн, что и у мобильного каталога), явное закрытие в
 * onClick каждой ссылки.
 */
export default function MobileNavDrawer({
  open,
  onClose,
  items,
  onRequestClick,
  requestLabel,
}: {
  open: boolean;
  onClose: () => void;
  items: NavItem[];
  onRequestClick: () => void;
  requestLabel: string;
}) {
  const overlayRef = useRef<HTMLDivElement>(null);
  const drawerRef = useRef<HTMLDivElement>(null);
  const backdropRef = useRef<HTMLDivElement>(null);
  const tlRef = useRef<gsap.core.Timeline | null>(null);
  const mountedRef = useRef(false);
  // Флаг именно для рендера портала — createPortal(..., document.body)
  // не может вызываться на сервере ("document is not defined"). mountedRef
  // выше — это ref (не триггерит ре-рендер), для решения "рендерить портал
  // или нет" нужен настоящий state, выставляемый только на клиенте после
  // маунта (тот же паттерн, что и в MegaMenu.tsx).
  const [mounted, setMounted] = useState(false);
  useEffect(() => setMounted(true), []);

  // строим таймлайн ПОСЛЕ того, как mounted стал true — то есть после того,
  // как портал реально отрендерился и рефы указывают на настоящие DOM-
  // элементы. Раньше зависимость была [] (пустая) — эффект срабатывал на
  // самом первом рендере, когда компонент из-за !mounted ещё возвращал
  // null и рефы были null; таймлайн строился "в никуда" и tl.play()
  // потом ничего не анимировал — отсюда и не выезжающее меню.
  useEffect(() => {
    if (!mounted) return;
    mountedRef.current = true;
    const ctx = gsap.context(() => {
      gsap.set(overlayRef.current, { visibility: 'hidden', pointerEvents: 'none' });
      gsap.set(drawerRef.current, { xPercent: 105 });
      gsap.set(backdropRef.current, { autoAlpha: 0 });

      tlRef.current = gsap
        .timeline({ paused: true })
        .set(overlayRef.current, { visibility: 'visible', pointerEvents: 'auto' })
        .to(backdropRef.current, { autoAlpha: 1, duration: 0.24, ease: 'power2.out' }, 0)
        .to(drawerRef.current, { xPercent: 0, duration: 0.38, ease: 'power3.out' }, 0.02);
    });
    return () => ctx.revert();
  }, [mounted]);

  useEffect(() => {
    const tl = tlRef.current;
    if (!tl) return;
    if (open) {
      document.body.style.overflow = 'hidden';
      tl.eventCallback('onReverseComplete', null);
      tl.play();
    } else if (mountedRef.current) {
      tl.eventCallback('onReverseComplete', () => {
        gsap.set(overlayRef.current, { visibility: 'hidden', pointerEvents: 'none' });
      });
      tl.reverse();
      document.body.style.overflow = '';
    }
  }, [open]);

  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && open) onClose();
    };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [open, onClose]);

  if (!mounted) return null;

  return createPortal(
    <div className="mNav" ref={overlayRef}>
      <div className="mNav__backdrop" ref={backdropRef} onClick={onClose} aria-hidden />
      <aside className="mNav__drawer" ref={drawerRef} role="dialog" aria-modal="true">
        <div className="mNav__head">
          <span className="mNav__title">Меню</span>
          <button type="button" className="mNav__close" onClick={onClose} aria-label="Закрыть">×</button>
        </div>

        <nav className="mNav__list">
          {items.map((item) => (
            <Link key={item.href} href={item.href} className="mNav__link" onClick={onClose}>
              {item.icon === 'home' ? (
                <i className="fa-solid fa-house-chimney" aria-hidden />
              ) : (
                <span className="mNav__linkText">{item.label}</span>
              )}
            </Link>
          ))}
        </nav>

        <button
          type="button"
          className="mNav__cta"
          onClick={() => {
            onClose();
            onRequestClick();
          }}
        >
          <svg className="mNav__cta-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
            <rect x="3" y="5" width="18" height="14" rx="2" />
            <path d="m3 7 9 6 9-6" />
          </svg>
          <span className="mNav__cta-text">{requestLabel}</span>
        </button>
      </aside>
    </div>,
    document.body
  );
}
