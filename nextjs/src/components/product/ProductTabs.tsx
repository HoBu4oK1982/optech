'use client';

import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import './product.css';
import { absolutizeRichContent } from '@/lib/utils';

export type ProductTab = { id: string; label: string; shortLabel?: string; html: string };

export default function ProductTabs({ tabs }: { tabs: ProductTab[] }) {
  const [active, setActive] = useState(0);
  if (!tabs.length) return null;

  const idx = Math.min(active, tabs.length - 1);
  const current = tabs[idx];

  return (
    <div className="productTabs">
      <div className="productTabs__nav" role="tablist">
        {tabs.map((tab, i) => {
          const isActive = i === idx;
          return (
            <button
              key={tab.id}
              type="button"
              role="tab"
              aria-selected={isActive}
              className={`productTabs__btn${isActive ? ' productTabs__btn--active' : ''}`}
              onClick={() => setActive(i)}
            >
              <span className="productTabs__btnFull">{tab.label}</span>
              <span className="productTabs__btnShort">{tab.shortLabel || tab.label}</span>
              {isActive && (
                <motion.span
                  layoutId="productTabInk"
                  className="productTabs__ink"
                  transition={{ type: 'spring', stiffness: 480, damping: 38 }}
                />
              )}
            </button>
          );
        })}
      </div>

      <AnimatePresence mode="wait">
        <motion.div
          key={current.id}
          className="productTabs__panel rich-content"
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          exit={{ opacity: 0, y: -6 }}
          transition={{ duration: 0.24, ease: [0.22, 1, 0.36, 1] }}
          dangerouslySetInnerHTML={{ __html: absolutizeRichContent(current.html) }}
        />
      </AnimatePresence>
    </div>
  );
}
