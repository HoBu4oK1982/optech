'use client';

import { useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';
import gsap from 'gsap';
import SmartSearch from './SmartSearch';
import './mobileSearchOverlay.css';

/**
 * Полноэкранный поиск для мобильных — открывается по иконке-лупе в шапке.
 * Тёмный размытый фон + карточка поиска сверху (как референс TGR market),
 * но в фирменных цветах OPTECH. Сам инпут/выпадашка результатов — это
 * тот же SmartSearch, что и на десктопе (переиспользуем всю логику живого
 * поиска, а не дублируем её).
 */
export default function MobileSearchOverlay({
  locale,
  placeholder,
  onClose,
}: {
  locale: string;
  placeholder: string;
  onClose: () => void;
}) {
  const overlayRef = useRef<HTMLDivElement>(null);
  const cardRef = useRef<HTMLDivElement>(null);
  const closingRef = useRef(false);

  const close = () => {
    if (closingRef.current) return;
    closingRef.current = true;
    gsap.to(cardRef.current, { y: -16, autoAlpha: 0, duration: 0.2, ease: 'power2.in' });
    gsap.to(overlayRef.current, {
      autoAlpha: 0,
      duration: 0.24,
      ease: 'power2.in',
      onComplete: onClose,
    });
  };

  useEffect(() => {
    document.body.style.overflow = 'hidden';

    const ctx = gsap.context(() => {
      gsap.set(overlayRef.current, { autoAlpha: 0 });
      gsap.set(cardRef.current, { y: -18, autoAlpha: 0 });
      gsap
        .timeline()
        .to(overlayRef.current, { autoAlpha: 1, duration: 0.22, ease: 'power2.out' })
        .to(cardRef.current, { y: 0, autoAlpha: 1, duration: 0.32, ease: 'power3.out' }, '-=0.12');
    });

    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') close();
    };
    document.addEventListener('keydown', onKey);

    // Автофокус на поле ввода — как в референсе, сразу готово к вводу.
    const focusTimer = window.setTimeout(() => {
      overlayRef.current?.querySelector<HTMLInputElement>('.ss__input')?.focus();
    }, 260);

    return () => {
      document.body.style.overflow = '';
      document.removeEventListener('keydown', onKey);
      window.clearTimeout(focusTimer);
      ctx.revert();
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return createPortal(
    <div className="mSearch" ref={overlayRef}>
      <div className="mSearch__backdrop" onClick={close} aria-hidden />

      <div className="mSearch__card" ref={cardRef} role="dialog" aria-modal="true">
        <div className="mSearch__head">
          <span className="mSearch__title">Поиск по каталогу</span>
          <button type="button" className="mSearch__close" onClick={close} aria-label="Закрыть поиск">
            ×
          </button>
        </div>

        <div className="mSearch__body">
          <SmartSearch locale={locale} placeholder={placeholder} onNavigate={close} />
        </div>

        <div className="mSearch__hint">
          Нажмите <kbd>Esc</kbd>, чтобы закрыть
        </div>
      </div>
    </div>,
    document.body
  );
}
