'use client';

import Link from 'next/link';
import { motion } from 'framer-motion';
import { BACKEND_URL } from '@/lib/constants';
import './brands.css';

type Brand = {
  id: number;
  name: string;
  slug: string;
  image?: string | null;
};

export default function BrandsGrid({
  brands,
  locale,
}: {
  brands: Brand[];
  locale: string;
}) {
  if (!brands?.length) return null;

  return (
    <div className="brandsGrid">
      {brands.map((brand, i) => (
        <motion.div
          key={brand.id}
          className="brandsGrid__cell"
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: '-40px' }}
          transition={{
            duration: 0.45,
            delay: (i % 4) * 0.06,
            ease: [0.22, 1, 0.36, 1],
          }}
        >
          <Link href={`/${locale}/brand/${brand.slug}`} className="brandCard">
            <div className="brandCard__logo">
              {brand.image ? (
                <img
                  src={`${BACKEND_URL}/assets/images/brands/${brand.image}`}
                  alt={brand.name}
                  loading="lazy"
                  onError={(e) => {
                    (e.currentTarget as HTMLImageElement).style.display = 'none';
                  }}
                />
              ) : (
                <span className="brandCard__placeholder">{brand.name}</span>
              )}
            </div>
            <span className="brandCard__name">{brand.name}</span>
          </Link>
        </motion.div>
      ))}
    </div>
  );
}
