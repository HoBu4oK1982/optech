'use client';

import Image from 'next/image';
import Link from 'next/link';
import { motion } from 'framer-motion';
import { BACKEND_URL } from '@/lib/constants';
import './catalog.css';

export type CatalogItem = {
  id: number | string;
  name: string;
  href: string;
  image?: string | null;
  kind: 'category' | 'product';
  sku?: string | null;
};

function hideOnError(e: React.SyntheticEvent<HTMLImageElement>) {
  (e.currentTarget as HTMLImageElement).style.display = 'none';
}

/**
 * Сетка каталога.
 *  - categoryVariant="button" (корень /catalog): карточки категорий в стиле
 *    главной (.homeBestCatItem) с кнопкой «Перейти в раздел».
 *  - categoryVariant="link"  (категория/подкатегория/подподкатегория, по умолч.):
 *    карточка картинка+название, целиком кликабельная, без кнопки.
 *  Товары всегда: картинка+название(+артикул), целиком кликабельные, без кнопки.
 *
 *  Пути картинок: категории — /assets/images/categories/{image},
 *                 товары    — /assets/images/products/{image}.
 */
export default function CatalogItems({
  items,
  ctaCategory,
  skuLabel,
  categoryVariant = 'link',
}: {
  items: CatalogItem[];
  ctaCategory?: string;
  ctaProduct?: string;
  skuLabel?: string;
  categoryVariant?: 'button' | 'link';
}) {
  if (!items?.length) return null;

  return (
    <div className="catalogGrid">
      {items.map((item, i) => {
        const isProduct = item.kind === 'product';
        const folder = isProduct ? 'products' : 'categories';
        const imgSrc = item.image
          ? `${BACKEND_URL}/assets/images/${folder}/${item.image}`
          : null;

        const media = imgSrc ? (
          <Image
            src={imgSrc}
            alt={item.name}
            fill
            sizes="(max-width: 600px) 45vw, (max-width: 1024px) 30vw, 300px"
            onError={hideOnError}
          />
        ) : (
          <span className="catCard__placeholder" />
        );

        return (
          <motion.div
            key={`${item.kind}-${item.id}`}
            className="catalogGrid__cell"
            initial={{ opacity: 0, y: 22 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: '-40px' }}
            transition={{ duration: 0.45, delay: (i % 4) * 0.06, ease: [0.22, 1, 0.36, 1] }}
          >
            {isProduct ? (
              // ---- товар: картинка + название (+ артикул), целиком кликабельно ----
              <Link href={item.href} className="catCard catCard--product">
                <div className="catCard__media catCard__media--top">{media}</div>
                <h3 className="catCard__title catCard__title--product">{item.name}</h3>
                {item.sku ? (
                  <p className="catCard__sku">
                    {skuLabel}: {item.sku}
                  </p>
                ) : null}
              </Link>
            ) : categoryVariant === 'button' ? (
              // ---- корневой каталог: карточка как на главной + кнопка ----
              <div className="homeBestCatItem">
                <h3>{item.name}</h3>
                {media}
                <Link href={item.href} className="homeBestBtn">
                  {ctaCategory}
                </Link>
              </div>
            ) : (
              // ---- категория/подкатегория: картинка + название, целиком кликабельно ----
              <Link href={item.href} className="catCard catCard--product">
                <div className="catCard__media catCard__media--top">{media}</div>
                <h3 className="catCard__title catCard__title--category">{item.name}</h3>
              </Link>
            )}
          </motion.div>
        );
      })}
    </div>
  );
}
