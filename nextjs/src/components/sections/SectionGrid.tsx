'use client';

import Image from 'next/image';
import Link from 'next/link';
import { motion } from 'framer-motion';
import './sectionGrid.css';

export type SectionItem = {
  id: number | string;
  title: string;
  href: string;
  imageUrl?: string | null;
  /** image_alt / image_title из админки; без них alt подставляется из заголовка. */
  imageAlt?: string | null;
  imageTitle?: string | null;
  excerpt?: string | null;
  date?: string | null;
};

function hideOnError(e: React.SyntheticEvent<HTMLImageElement>) {
  (e.currentTarget as HTMLImageElement).style.display = 'none';
}

/**
 * Единая карточка для разделов-списков (Решения, Услуги, Проекты,
 * Новости, Спецпредложения) — тот же визуальный язык, что и каталог.
 */
export default function SectionGrid({
  items,
  ctaLabel,
  showCta = true,
  variant = 'default',
}: {
  items: SectionItem[];
  ctaLabel?: string;
  showCta?: boolean;
  variant?: 'default' | 'news' | 'solutions' | 'services' | 'projects';
}) {
  if (!items?.length) return null;

  return (
    <div className={`secGrid secGrid--${variant}`}>
      {items.map((it, i) => (
        <motion.div
          key={it.id}
          className="secGrid__cell"
          initial={{ opacity: 0, y: 22 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: '-40px' }}
          transition={{ duration: 0.45, delay: (i % 4) * 0.05, ease: [0.22, 1, 0.36, 1] }}
        >
          <Link href={it.href} className={`secCard secCard--${variant}`} aria-label={it.title}>
            <div className="secCard__cover">
              {it.imageUrl ? (
                <Image
                  src={it.imageUrl}
                  alt={it.imageAlt || it.title}
                  title={it.imageTitle || undefined}
                  fill
                  sizes="(max-width: 600px) 100vw, (max-width: 1024px) 50vw, 25vw"
                  onError={hideOnError}
                />
              ) : (
                <span className="secCard__ph" />
              )}
              {it.date ? <span className="secCard__date">{it.date}</span> : null}
            </div>
            <div className="secCard__body">
              <h2 className="secCard__title">{it.title}</h2>
              {it.excerpt ? <p className="secCard__excerpt">{it.excerpt}</p> : null}
              {showCta && ctaLabel ? <span className="secCard__cta">{ctaLabel}</span> : null}
            </div>
          </Link>
        </motion.div>
      ))}
    </div>
  );
}
