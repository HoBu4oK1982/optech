'use client';

import { useEffect, useState } from 'react';
import './floating.css';

export default function ScrollTopButton() {
  const [visible, setVisible] = useState(false);
  const [progress, setProgress] = useState(0);

  useEffect(() => {
    const onScroll = () => {
      const doc = document.documentElement;
      const max = doc.scrollHeight - doc.clientHeight;
      const y = window.scrollY;
      setVisible(y > 400);
      setProgress(max > 0 ? Math.min(1, y / max) : 0);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  const toTop = () => {
    const lenis = (window as unknown as { lenis?: { scrollTo: (t: number, o?: object) => void } }).lenis;
    if (lenis?.scrollTo) {
      lenis.scrollTo(0, { duration: 1.1 });
    } else {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  // голубая заливка снизу вверх по мере прокрутки
  const pct = Math.round(progress * 100);
  const fill = `linear-gradient(to top, #4eb0fb ${pct}%, #111b2f ${pct}%)`;

  return (
    <button
      type="button"
      className={`scrollTopBtn ${visible ? 'is-visible' : ''}`}
      onClick={toTop}
      aria-label="Наверх"
      style={{ background: fill, color: '#fff' }}
    >
      <svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="#ffffff" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" aria-hidden style={{ display: 'block' }}>
        <path d="M12 20V5M12 5l-7 7M12 5l7 7" />
      </svg>
    </button>
  );
}
