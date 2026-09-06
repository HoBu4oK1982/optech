'use client';

import { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import Image from 'next/image';
import Link from 'next/link';
import gsap from 'gsap';
import './megaMenu.css';

type Category = {
  id: number;
  name: string;
  slug: string;
  image?: string | null;
  children?: Category[] | null;
};

type Props = {
  categories: Category[];
  locale: string;
  backendUrl: string;
  label: string;
  /** Когда true — триггер-кнопка внутри MegaMenu не рендерится, а открытием
   *  управляет родитель (мобильный бургер в шапке). */
  controlled?: boolean;
  /** Внешнее состояние открытия (для controlled-режима). */
  externalOpen?: boolean;
  /** Колбэк на закрытие (для controlled-режима). */
  onClose?: () => void;
};

export default function MegaMenu({
  categories,
  locale,
  backendUrl,
  label,
  controlled = false,
  externalOpen,
  onClose,
}: Props) {
  const [internalOpen, setInternalOpen] = useState(false);
  const open = controlled ? !!externalOpen : internalOpen;
  const setOpen = (v: boolean | ((p: boolean) => boolean)) => {
    if (controlled) {
      const next = typeof v === 'function' ? (v as (p: boolean) => boolean)(open) : v;
      if (!next) onClose?.();
    } else {
      setInternalOpen(v);
    }
  };

  const [mounted, setMounted] = useState(false);
  const [isMobile, setIsMobile] = useState(false);
  // На мобиле категория-родитель не ведёт ссылкой, а раскрывает детей —
  // храним id раскрытого родителя (аккордеон).
  const [expandedId, setExpandedId] = useState<number | null>(null);
  const [activeId, setActiveId] = useState<number | null>(null);
  const [pos, setPos] = useState<{ top: number; left: number; height: number }>({ top: 0, left: 0, height: 520 });
  const [trigRect, setTrigRect] = useState<{ top: number; left: number; height: number }>({ top: 0, left: 0, height: 0 });

  const triggerRef = useRef<HTMLButtonElement>(null);
  const portalRef = useRef<HTMLDivElement>(null);
  const panelRef = useRef<HTMLDivElement>(null);
  const mobileDrawerRef = useRef<HTMLDivElement>(null);
  const mobileOverlayRef = useRef<HTMLDivElement>(null);
  const rightRef = useRef<HTMLDivElement>(null);
  const tlRef = useRef<gsap.core.Timeline | null>(null);
  const mobileTlRef = useRef<gsap.core.Timeline | null>(null);

  const L = (path: string) => `/${locale}${path}`;
  const apiBase = backendUrl.replace(/\/$/, '');
  const getCategoryImage = (category?: Category | null) => {
    if (!category?.image) return '';
    if (/^https?:\/\//i.test(category.image)) return category.image;
    return `${apiBase}/assets/images/categories/${category.image}`;
  };

  const active = categories.find((c) => c.id === activeId) ?? categories[0] ?? null;
  const activeImage = getCategoryImage(active);
  const activeChildren = active?.children ?? [];

  useEffect(() => setMounted(true), []);

  // Определяем мобильный вьюпорт (совпадает с брейкпоинтом шапки — 768px).
  useEffect(() => {
    const mq = window.matchMedia('(max-width: 768px)');
    const apply = () => setIsMobile(mq.matches);
    apply();
    mq.addEventListener('change', apply);
    return () => mq.removeEventListener('change', apply);
  }, []);

  // Блокируем скролл body, пока открыто мобильное меню.
  useEffect(() => {
    if (!isMobile) return;
    document.body.style.overflow = open ? 'hidden' : '';
    return () => {
      document.body.style.overflow = '';
    };
  }, [open, isMobile]);

  // позиция панели (fixed): по левому краю контейнера (1320), под кнопкой
  useEffect(() => {
    if (!open || !triggerRef.current) return;
    const update = () => {
      const r = triggerRef.current!.getBoundingClientRect();
      const panelW = Math.min(1320, window.innerWidth - 32);
      const left = Math.max(16, (window.innerWidth - panelW) / 2);
      const top = r.bottom + 12;
      const height = Math.max(320, window.innerHeight - top - 18);
      setPos({ top, left, height });
      setTrigRect({ top: r.top, left: r.left, height: r.height });
    };
    update();
    window.addEventListener('resize', update);
    window.addEventListener('scroll', update, { passive: true });
    return () => {
      window.removeEventListener('resize', update);
      window.removeEventListener('scroll', update);
    };
  }, [open]);

  // open/close timeline (scoped к порталу) — только для десктопной панели.
  useEffect(() => {
    if (!mounted || isMobile) return;
    const ctx = gsap.context(() => {
      tlRef.current = gsap
        .timeline({ paused: true })
        .set(panelRef.current, { display: 'grid' })
        .fromTo(
          panelRef.current,
          { autoAlpha: 0, y: -14 },
          { autoAlpha: 1, y: 0, duration: 0.34, ease: 'power3.out' }
        )
        .fromTo(
          '.mm__parent',
          { autoAlpha: 0, x: -12 },
          { autoAlpha: 1, x: 0, stagger: 0.03, duration: 0.26, ease: 'power2.out' },
          '-=0.16'
        )
        .fromTo('.mm__right', { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.28 }, '<');
    }, portalRef);
    return () => ctx.revert();
  }, [mounted, isMobile]);

  useEffect(() => {
    if (isMobile) return;
    const tl = tlRef.current;
    if (!tl) return;
    open ? tl.play() : tl.reverse();
  }, [open, isMobile]);

  // Мобильный drawer — плавный выезд/заезд слева на GSAP (замена CSS-
  // transition, которая раньше конфликтовала с моментальным React-классом
  // is-open при закрытии — панель "срезалась" без анимации). Видимость
  // корня (.mmMobile) и его pointer-events включаются/выключаются самим
  // таймлайном в нужный момент, а не CSS-классом синхронно с React-стейтом.
  useEffect(() => {
    if (!mounted || !isMobile) return;
    const ctx = gsap.context(() => {
      gsap.set(portalRef.current, { visibility: 'hidden', pointerEvents: 'none' });
      gsap.set(mobileDrawerRef.current, { xPercent: -105 });
      gsap.set(mobileOverlayRef.current, { autoAlpha: 0 });

      mobileTlRef.current = gsap
        .timeline({ paused: true })
        .set(portalRef.current, { visibility: 'visible', pointerEvents: 'auto' })
        .to(mobileOverlayRef.current, { autoAlpha: 1, duration: 0.26, ease: 'power2.out' }, 0)
        .to(mobileDrawerRef.current, { xPercent: 0, duration: 0.4, ease: 'power3.out' }, 0.02);
    }, portalRef);
    return () => ctx.revert();
  }, [mounted, isMobile]);

  useEffect(() => {
    if (!isMobile) return;
    const tl = mobileTlRef.current;
    if (!tl) return;
    if (open) {
      tl.eventCallback('onReverseComplete', null);
      tl.play();
    } else {
      tl.eventCallback('onReverseComplete', () => {
        gsap.set(portalRef.current, { visibility: 'hidden', pointerEvents: 'none' });
      });
      tl.reverse();
    }
  }, [open, isMobile]);

  // При открытом меню не блокируем скролл страницы: пользователю можно прокручивать фон,
  // а сама панель мегаменю имеет внутренний scroll по высоте окна.

  // crossfade right on category change (desktop only)
  useEffect(() => {
    if (!open || isMobile || !rightRef.current) return;
    gsap.fromTo(
      rightRef.current,
      { autoAlpha: 0, x: 12 },
      { autoAlpha: 1, x: 0, duration: 0.3, ease: 'power2.out' }
    );
  }, [activeId, open, isMobile]);

  // escape + outside click
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setOpen(false);
    };
    const onDown = (e: MouseEvent) => {
      const target = e.target as Node;
      const inTrigger = triggerRef.current?.contains(target);
      const inPanel = panelRef.current?.contains(target);
      const inMobileDrawer = mobileDrawerRef.current?.contains(target);
      if (!inTrigger && !inPanel && !inMobileDrawer) setOpen(false);
    };
    document.addEventListener('keydown', onKey);
    document.addEventListener('mousedown', onDown);
    return () => {
      document.removeEventListener('keydown', onKey);
      document.removeEventListener('mousedown', onDown);
    };
  }, []);

  if (!categories?.length) return null;

  // ---------- Мобильная панель (аккордеон) ----------
  // Родитель = кнопка-раскрывашка (без перехода), дети = ссылки.
  const mobilePortal = (
    <div className="mmMobile" ref={portalRef}>
      <div className="mmMobile__overlay" ref={mobileOverlayRef} onClick={() => setOpen(false)} aria-hidden />
      <aside className="mmMobile__drawer" ref={mobileDrawerRef} role="dialog" aria-modal="true">
        <div className="mmMobile__head">
          <span className="mmMobile__title">{label}</span>
          <button type="button" className="mmMobile__close" onClick={() => setOpen(false)} aria-label="Закрыть">×</button>
        </div>

        <ul className="mmMobile__list">
          {categories.map((cat) => {
            const kids = cat.children ?? [];
            const isExpanded = expandedId === cat.id;
            const hasKids = kids.length > 0;
            return (
              <li key={cat.id} className={`mmMobile__item ${isExpanded ? 'is-expanded' : ''}`}>
                <button
                  type="button"
                  className="mmMobile__parent"
                  onClick={() => setExpandedId(isExpanded ? null : cat.id)}
                  aria-expanded={isExpanded}
                >
                  {cat.image ? (
                    <Image
                      className="mmMobile__ico"
                      src={getCategoryImage(cat)}
                      alt=""
                      width={34}
                      height={34}
                      loading="lazy"
                      onError={(e) => {
                        (e.currentTarget as HTMLImageElement).style.visibility = 'hidden';
                      }}
                    />
                  ) : (
                    <span className="mmMobile__ico mmMobile__ico--empty" />
                  )}
                  <span className="mmMobile__name">{cat.name}</span>
                  <span className="mmMobile__caret" aria-hidden>›</span>
                </button>

                {isExpanded && (
                  <div className="mmMobile__children">
                    {hasKids ? (
                      <>
                        <Link
                          href={L(`/catalog/${cat.slug}`)}
                          className="mmMobile__child mmMobile__child--all"
                          onClick={() => setOpen(false)}
                        >
                          Весь раздел «{cat.name}»
                        </Link>
                        {kids.map((child) => (
                          <Link
                            key={child.id}
                            href={L(`/catalog/${cat.slug}/${child.slug}`)}
                            className="mmMobile__child"
                            onClick={() => setOpen(false)}
                          >
                            {child.name}
                          </Link>
                        ))}
                      </>
                    ) : (
                      // У категории нет подкатегорий — раньше в этом случае
                      // стрелки не было вообще, и попасть в раздел с мобилы
                      // было нельзя никак. Даём единственный пункт-переход.
                      <Link
                        href={L(`/catalog/${cat.slug}`)}
                        className="mmMobile__child mmMobile__child--all"
                        onClick={() => setOpen(false)}
                      >
                        Перейти в раздел
                      </Link>
                    )}
                  </div>
                )}
              </li>
            );
          })}
        </ul>
      </aside>
    </div>
  );

  // ---------- Десктопная панель (как было) ----------
  const portal = (
    <div className={`mmPortal ${open ? 'is-open' : ''}`} ref={portalRef}>
      {/* тёмно-синий полупрозрачный оверлей */}
      <div className="mm__overlay" onClick={() => setOpen(false)} aria-hidden />

      {/* яркая копия кнопки поверх оверлея — только при открытом меню */}
      {open && (
        <button
          type="button"
          className="mm__trigger mm__trigger--float is-open"
          style={{ position: 'fixed', top: trigRect.top, left: trigRect.left, height: trigRect.height }}
          onClick={() => setOpen(false)}
          aria-label={label}
        >
          <span className="mm__burger is-open">
            <i></i>
            <i></i>
            <i></i>
          </span>
          <span className="mm__trigger-label">{label}</span>
        </button>
      )}

      <div className="mm__panel" ref={panelRef} style={{ top: pos.top, left: pos.left, height: pos.height }}>
        <ul className="mm__parents">
          {categories.map((cat) => (
            <li key={cat.id}>
              <Link
                href={L(`/catalog/${cat.slug}`)}
                className={`mm__parent ${active?.id === cat.id ? 'is-active' : ''}`}
                onMouseEnter={() => setActiveId(cat.id)}
                onFocus={() => setActiveId(cat.id)}
                onClick={() => setOpen(false)}
              >
                {cat.image ? (
                  <Image
                    className="mm__parent-ico"
                    src={getCategoryImage(cat)}
                    alt=""
                    width={38}
                    height={38}
                    loading="lazy"
                    onError={(e) => {
                      (e.currentTarget as HTMLImageElement).style.visibility = 'hidden';
                    }}
                  />
                ) : (
                  <span className="mm__parent-ico mm__parent-ico--empty" />
                )}
                <span className="mm__parent-name">{cat.name}</span>
                <span className="mm__parent-arrow" aria-hidden>›</span>
              </Link>
            </li>
          ))}
        </ul>

        <div className="mm__right" ref={rightRef}>
          {active && (
            <>
              <div className="mm__compact-head">
                <div className="mm__compact-copy">
                  <Link
                    href={L(`/catalog/${active.slug}`)}
                    className="mm__right-title"
                    onClick={() => setOpen(false)}
                  >
                    {active.name}
                  </Link>
                </div>
                <Link
                  href={L(`/catalog/${active.slug}`)}
                  className="mm__section-link"
                  onClick={() => setOpen(false)}
                >
                  Весь раздел
                </Link>
              </div>

              <div className="mm__section-head">
                <span>Подкатегории</span>
                <b>{activeChildren.length}</b>
              </div>

              <div className="mm__children-grid">
                {activeChildren.map((child) => {
                  const childImage = getCategoryImage(child) || activeImage;
                  return (
                    <Link
                      key={child.id}
                      href={L(`/catalog/${active.slug}/${child.slug}`)}
                      className="mm__child-card"
                      onClick={() => setOpen(false)}
                    >
                      <span className={`mm__child-thumb ${childImage ? '' : 'is-empty'}`}>
                        {childImage ? (
                          <Image
                            src={childImage}
                            alt=""
                            width={74}
                            height={58}
                            loading="lazy"
                            onError={(e) => {
                              (e.currentTarget as HTMLImageElement).style.display = 'none';
                            }}
                          />
                        ) : (
                          <span>{child.name.slice(0, 2)}</span>
                        )}
                      </span>
                      <span className="mm__child-content">
                        <span className="mm__child-name">{child.name}</span>
                        <span className="mm__child-meta">Перейти в раздел</span>
                      </span>
                      <span className="mm__child-go" aria-hidden>→</span>
                    </Link>
                  );
                })}

                {activeChildren.length === 0 && (
                  <Link
                    href={L(`/catalog/${active.slug}`)}
                    className="mm__child-card mm__child-card--all"
                    onClick={() => setOpen(false)}
                  >
                    <span className="mm__child-thumb is-empty"><span>OP</span></span>
                    <span className="mm__child-content">
                      <span className="mm__child-name">Смотреть раздел</span>
                      <span className="mm__child-meta">Все товары категории</span>
                    </span>
                    <span className="mm__child-go" aria-hidden>→</span>
                  </Link>
                )}
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );

  const activePortal = isMobile ? mobilePortal : portal;

  return (
    <div className={`mm ${open ? 'is-open' : ''}`}>
      {!controlled && (
        <button
          type="button"
          className="mm__trigger"
          ref={triggerRef}
          aria-expanded={open}
          aria-haspopup="true"
          onClick={() => setOpen((v) => !v)}
        >
          <span className={`mm__burger ${open ? 'is-open' : ''}`}>
            <i></i>
            <i></i>
            <i></i>
          </span>
          <span className="mm__trigger-label">{label}</span>
        </button>
      )}

      {mounted && createPortal(activePortal, document.body)}
    </div>
  );
}
