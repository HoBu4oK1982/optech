import Link from 'next/link';
import './catalog.css';

const PERIODS: Array<{ key: string; label: string }> = [
  { key: 'all', label: 'Все товары' },
  { key: 'week', label: 'За неделю' },
  { key: 'month', label: 'За месяц' },
  { key: 'quarter', label: 'За 3 месяца' },
];

/**
 * Фильтр по дате добавления товара — над сеткой карточек на страницах
 * категории/подкатегории/под-подкатегории. Ссылки на ту же страницу с
 * query-параметром ?period=, без клиентского JS (та же схема, что и
 * .searchTabs на странице поиска) — сортировка «новые сверху» на сервере
 * не зависит от выбора здесь и применяется всегда.
 */
export default function CatalogDateFilter({
  basePath,
  current,
}: {
  basePath: string;
  current?: string;
}) {
  const active = PERIODS.some((p) => p.key === current) ? current : 'all';

  return (
    <nav className="catDateFilter" aria-label="Фильтр по дате добавления">
      {PERIODS.map((p) => {
        const href = p.key === 'all' ? basePath : `${basePath}?period=${p.key}`;
        return (
          <Link
            key={p.key}
            href={href}
            className={`catDateFilter__pill ${active === p.key ? 'is-active' : ''}`}
          >
            {p.label}
          </Link>
        );
      })}
    </nav>
  );
}
