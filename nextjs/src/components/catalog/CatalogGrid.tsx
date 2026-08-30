'use client';

import Link from 'next/link';
import { motion } from 'framer-motion';
import { BACKEND_URL } from '@/lib/constants';
import './catalog.css';

type Category = {
  id: number;
  name: string;
  slug: string;
  image?: string | null;
  children?: { id: number }[] | null;
};

export default function CatalogGrid({
  categories,
  locale,
  ctaLabel,
}: {
  categories: Category[];
  locale: string;
  ctaLabel: string;
}) {
  if (!categories?.length) return null;

  return (
    <div className="catalogGrid">
      {categories.map((cat, i) => (
        <motion.div
          key={cat.id}
          className="catalogGrid__cell"
          initial={{ opacity: 0, y: 22 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: '-40px' }}
          transition={{ duration: 0.45, delay: (i % 4) * 0.06, ease: [0.22, 1, 0.36, 1] }}
        >
          <Link href={`/${locale}/catalog/${cat.slug}`} className="catCard">
            <h2 className="catCard__title">{cat.name}</h2>

            <div className="catCard__media">
              {cat.image ? (
                <img
                  src={`${BACKEND_URL}/assets/images/categories/${cat.image}`}
                  alt={cat.name}
                  loading="lazy"
                  onError={(e) => {
                    (e.currentTarget as HTMLImageElement).style.display = 'none';
                  }}
                />
              ) : (
                <span className="catCard__placeholder" />
              )}
            </div>

            <span className="catCard__cta">{ctaLabel}</span>
          </Link>
        </motion.div>
      ))}
    </div>
  );
}
