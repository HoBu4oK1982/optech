'use client';

import { useEffect, useRef, useState } from 'react';
import Link from 'next/link';
import { makeT } from '@/i18n/dict';
import MegaMenu from './MegaMenu';
import OptechLogo from '@/components/ui/OptechLogo';
import './floatingNav.css';

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
};

export default function FloatingNav({ categories, locale, backendUrl }: Props) {
  const t = makeT(locale);
  const L = (path: string) => `/${locale}${path}`;
  const [visible, setVisible] = useState(false);
  const lastY = useRef(0);

  // показываем при скролле вверх, прячем при скролле вниз
  useEffect(() => {
    lastY.current = window.scrollY;
    const onScroll = () => {
      const y = window.scrollY;
      if (y < 320) {
        setVisible(false);
      } else if (y < lastY.current - 4) {
        setVisible(true);
      } else if (y > lastY.current + 4) {
        setVisible(false);
      }
      lastY.current = y;
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  const openRequest = () => {
    window.dispatchEvent(new CustomEvent('optech:open-request'));
  };

  const nav = [
    { href: L('/brands'), label: t('brands_page_title') },
    { href: L('/projects'), label: t('projects_page_title') },
    { href: L('/articles'), label: t('news_page_title') },
    { href: L('/basa-znani'), label: t('knowledge_base_page_title') },
    { href: L('/solutions'), label: t('solutions_page_title') },
    { href: L('/services'), label: t('service_page_title') },
  ];

  return (
    <div className={`floatNav ${visible ? 'is-visible' : ''}`}>
      <div className="floatNav__inner">
        {/* logo */}
        <Link href={L('/')} className="floatNav__logo" aria-label="OPTECH">
          <OptechLogo width={150} height={30} />
        </Link>

        {/* catalog */}
        <div className="floatNav__catalog">
          <MegaMenu
            categories={categories}
            locale={locale}
            backendUrl={backendUrl}
            label={t('catalog_page_title')}
          />
        </div>

        {/* nav links */}
        <nav className="floatNav__links">
          <Link href={L('/')} className="floatNav__home" aria-label="Главная">
            <svg viewBox="0 0 576 512" aria-hidden="true" focusable="false">
              <path d="M575.8 255.5c0 18-15 32.1-32 32.1h-32l.7 160.2c0 2.7-.2 5.4-.5 8.1v16.2c0 22.1-17.9 40-40 40h-56c-22.1 0-40-17.9-40-40v-88c0-17.7-14.3-32-32-32H232c-17.7 0-32 14.3-32 32v88c0 22.1-17.9 40-40 40h-56c-22.1 0-40-17.9-40-40V359.7c0-.9 0-1.9.1-2.8v-69.3h-32c-18 0-32-14-32-32.1 0-9 3-17 10-24L266.4 8c7-7 15-8 22-8s15 2 21 7l255.4 224.5c8 7 12 15 11 24z" />
            </svg>
          </Link>
          {nav.map((item) => (
            <Link key={item.href} href={item.href}>
              {item.label}
            </Link>
          ))}
        </nav>

        {/* request */}
        <button type="button" className="floatNav__cta" onClick={openRequest}>
          <svg className="floatNav__cta-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
            <rect x="3" y="5" width="18" height="14" rx="2" />
            <path d="m3 7 9 6 9-6" />
          </svg>
          <span className="floatNav__cta-text">
            {t('submit_btn')
              .replace(/<1\s*\/>/g, '\n')
              .split('\n')
              .map((line, i) => (
                <span key={i}>{line}</span>
              ))}
          </span>
        </button>
      </div>
    </div>
  );
}
