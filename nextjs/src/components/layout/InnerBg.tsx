'use client';

import { usePathname } from 'next/navigation';
import { useEffect } from 'react';

// точечный фон на всех внутренних страницах, кроме главной
export default function InnerBg() {
  const pathname = usePathname();

  useEffect(() => {
    const isHome = /^\/[a-z]{2}\/?$/.test(pathname || '');
    document.body.classList.toggle('has-dots', !isHome);
    return () => document.body.classList.remove('has-dots');
  }, [pathname]);

  return null;
}
