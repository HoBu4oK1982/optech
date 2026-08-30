'use client';

/**
 * CookieConsent — уведомление об использовании cookie.
 *
 * Показывается один раз (пока пользователь не нажал «Понятно» — тогда
 * ставим флаг в localStorage и больше не показываем). Небольшая задержка
 * перед появлением, чтобы не спорить с прелоадером/входной анимацией
 * первой страницы. Лёгкая CSS-анимация (slide-up), без GSAP — для такой
 * простой одноразовой анимации отдельная библиотека не нужна.
 */

import { useEffect, useState } from 'react';
import './cookieConsent.css';

const STORAGE_KEY = 'optech_cookie_consent';

export default function CookieConsent() {
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    let accepted = false;
    try {
      accepted = localStorage.getItem(STORAGE_KEY) === '1';
    } catch {
      accepted = false;
    }
    if (accepted) return;

    const timer = window.setTimeout(() => setVisible(true), 900);
    return () => window.clearTimeout(timer);
  }, []);

  const accept = () => {
    try {
      localStorage.setItem(STORAGE_KEY, '1');
    } catch {
      /* ignore — если localStorage недоступен, просто скрываем на сессию */
    }
    setVisible(false);
  };

  return (
    <div
      className={`cookieConsent ${visible ? 'is-visible' : ''}`}
      role="region"
      aria-label="Уведомление об использовании cookie"
    >
      <div className="cookieConsent__inner">
        <span className="cookieConsent__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6">
            <path d="M12 2.5c-1 1.4-.4 2.9 1 3 1.6.1 2.6 1.3 2.4 2.7 1.5-.3 2.9.7 3.1 2.2A9.5 9.5 0 1 1 12 2.5Z" />
            <circle cx="9" cy="10.5" r="1" fill="currentColor" stroke="none" />
            <circle cx="13.5" cy="14" r="1" fill="currentColor" stroke="none" />
            <circle cx="9.5" cy="15.5" r="1" fill="currentColor" stroke="none" />
            <circle cx="14.5" cy="9.5" r="1" fill="currentColor" stroke="none" />
          </svg>
        </span>

        <p className="cookieConsent__text">
          Мы используем файлы cookie, чтобы сайт работал быстрее и был удобнее для
          вас. Продолжая пользоваться сайтом, вы соглашаетесь с их использованием.
        </p>

        <button type="button" className="cookieConsent__btn" onClick={accept}>
          Понятно
        </button>
      </div>
    </div>
  );
}
