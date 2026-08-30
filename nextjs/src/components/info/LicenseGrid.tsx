'use client';

import { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';

type Item = { id: number | string; src: string; alt: string };

export default function LicenseGrid({ items }: { items: Item[] }) {
  // Скрываем битые картинки, чтобы не оставалось пустых/сломанных плиток.
  const [broken, setBroken] = useState<Record<string, boolean>>({});
  const [zoom, setZoom] = useState<string | null>(null);

  const visible = items.filter((it) => !broken[String(it.id)] && it.src && !it.src.endsWith('/'));

  // Esc закрывает + блокируем скролл страницы, пока лайтбокс открыт —
  // тот же паттерн, что и у остальных модалок/оверлеев в проекте.
  useEffect(() => {
    if (!zoom) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setZoom(null);
    };
    document.addEventListener('keydown', onKey);
    const prevOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      document.removeEventListener('keydown', onKey);
      document.body.style.overflow = prevOverflow;
    };
  }, [zoom]);

  if (!visible.length) return null;

  return (
    <>
      <div className="infoDocs">
        {visible.map((it) => (
          <button
            key={it.id}
            type="button"
            className="infoDoc"
            onClick={() => setZoom(it.src)}
            aria-label={it.alt || 'Сертификат'}
          >
            <img
              src={it.src}
              alt={it.alt}
              loading="lazy"
              onError={() => setBroken((b) => ({ ...b, [String(it.id)]: true }))}
            />
          </button>
        ))}
      </div>

      {/* Портал в document.body — лайтбокс живёт внутри {children} → .pt-content
          (см. PageTransition.tsx), а туда GSAP временно ставит transform/
          will-change на время анимации перехода между страницами. Любой
          непустой transform/will-change на предке создаёт новый containing
          block для position:fixed потомков — тогда затемнение и картинка
          считают себя не от экрана, а от этого предка, и всё разъезжается
          (ровно та же причина, по которой раньше ломалась модалка
          ProductInquiry). Портал полностью выносит лайтбокс из этой
          стекинг-иерархии, как и у остальных модалок в проекте. */}
      {zoom &&
        createPortal(
          <div className="licenseLightbox" onClick={() => setZoom(null)} role="dialog" aria-modal="true">
            <img src={zoom} alt="" />
            <button type="button" className="licenseLightbox__close" onClick={() => setZoom(null)} aria-label="Закрыть">
              ×
            </button>
          </div>,
          document.body
        )}
    </>
  );
}
