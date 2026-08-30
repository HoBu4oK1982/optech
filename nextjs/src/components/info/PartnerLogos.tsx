'use client';

import { useState } from 'react';

type Item = { id: number | string; src: string; alt: string };

export default function PartnerLogos({ items }: { items: Item[] }) {
  const [broken, setBroken] = useState<Record<string, boolean>>({});
  const visible = items.filter((it) => !broken[String(it.id)] && it.src && !it.src.endsWith('/'));

  if (!visible.length) return null;

  return (
    <div className="infoLogos">
      {visible.map((it) => (
        <div className="infoLogo" key={it.id}>
          <img
            src={it.src}
            alt={it.alt}
            loading="lazy"
            onError={() => setBroken((b) => ({ ...b, [String(it.id)]: true }))}
          />
        </div>
      ))}
    </div>
  );
}
